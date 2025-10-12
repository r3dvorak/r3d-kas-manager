<?php
/**
 * R3D KAS Manager – KAS API Gateway (SOAP JSON wrapper)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.1-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/Recipes/KasGateway.php
 *
 * Purpose:
 *  Thin gateway around All-Inkl KAS "KasApi" SOAP endpoint. We pass a SINGLE
 *  JSON string to __soapCall('KasApi', [$json]) to avoid the PHP SOAP
 *  double-decoding issues. The response is normalized into:
 *    ['success' => bool, 'Response' => mixed] or ['success'=>false,'error'=>string]
 *
 * Notes:
 *  - $encryptedPassword: Your KasClient model should expose a decrypted
 *    accessor for account_password OR pass the raw (decrypted) string here.
 *  - On flood protection we auto-wait and retry once.
 */

namespace App\Services\Recipes;

use App\Models\KasClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SoapClient;
use Throwable;

class KasGateway
{
    /**
     * Low-level call: requires login + password (decrypted) string.
     *
     * @param string $kasLogin
     * @param string $password Decrypted account password (your KasClient model should expose readable accessor)
     * @param string $action
     * @param array  $params
     * @return array Normalized: ['success'=>bool, 'Response'=>mixed] or ['success'=>false,'error'=>string]
     */
    public function call(string $kasLogin, string $password, string $action, array $params = []): array
    {
        $wsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
        $soap = new SoapClient($wsdl, [
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace'      => true,
            'features'   => SOAP_SINGLE_ELEMENT_ARRAYS,
        ]);

        $payload = [
            'kas_login'        => $kasLogin,
            'kas_auth_type'    => 'plain',
            'kas_auth_data'    => $password,
            'kas_action'       => $action,
            'KasRequestParams' => $params,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $raw = $soap->__soapCall('KasApi', [$json]);

            // Normalize response safely:
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : ['Response' => ['Raw' => $raw]];
            } elseif (is_object($raw)) {
                $raw = json_decode(json_encode($raw), true) ?? [];
            } elseif (!is_array($raw)) {
                $raw = ['Response' => ['Raw' => $raw]];
            }

            $resp = $raw['Response'] ?? $raw;
            $ok   = (string)($resp['ReturnString'] ?? $raw['ReturnString'] ?? 'TRUE') === 'TRUE';

            return ['success' => $ok, 'Response' => $resp, 'raw' => $raw];
        } catch (\SoapFault $e) {
            $msg = $e->faultstring ?? $e->getMessage();

            // Flood protection simple retry
            if (stripos($msg, 'flood_protection') !== false) {
                sleep(2);
                try {
                    $raw = $soap->__soapCall('KasApi', [$json]);
                    $raw = is_object($raw) ? json_decode(json_encode($raw), true) : (array)$raw;
                    return ['success' => true, 'Response' => ($raw['Response'] ?? $raw)];
                } catch (Throwable $e2) {
                    return ['success' => false, 'error' => 'KAS SOAP error (retry): ' . $e2->getMessage()];
                }
            }

            return ['success' => false, 'error' => "KAS SOAP error: {$msg}"];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => "KAS SOAP error: " . $e->getMessage()];
        }
    }

    /**
     * Convenience: resolve KasClient by login and call using its stored password.
     *
     * @param string $kasLogin
     * @param string $action
     * @param array  $params
     * @return array
     */
    public function callForLogin(string $kasLogin, string $action, array $params = []): array
    {
        $client = KasClient::where('account_login', $kasLogin)->firstOrFail();

        // Ensure KasClient model returns the decrypted password in account_password accessor.
        return $this->call($kasLogin, $client->account_password, $action, $params);
    }

    /* ----------------------------------------------------------------
     * Mail / forward fetching helpers
     * ---------------------------------------------------------------- */

    /**
     * Fetch mailaccounts from KAS for $kasLogin, normalized to an array of associative records.
     *
     * @param string $kasLogin
     * @return array of account arrays
     */
    public function fetchMailaccounts(string $kasLogin): array
    {
        $resp = $this->callForLogin($kasLogin, 'get_mailaccounts', []);

        if (!($resp['success'] ?? false)) {
            Log::warning("fetchMailaccounts: KAS returned no success for {$kasLogin}", $resp);
            return [];
        }

        // Try collecting arrays that include a 'mail_login' key
        $raw = $resp['Response'] ?? $resp['raw'] ?? $resp;
        return $this->collectArraysWithKey($raw, 'mail_login');
    }

    /**
     * Fetch mailforwards from KAS for $kasLogin, normalized to an array of associative records.
     *
     * @param string $kasLogin
     * @return array
     */
    public function fetchMailforwards(string $kasLogin): array
    {
        $resp = $this->callForLogin($kasLogin, 'get_mailforwards', []);

        if (!($resp['success'] ?? false)) {
            Log::warning("fetchMailforwards: KAS returned no success for {$kasLogin}", $resp);
            return [];
        }

        $raw = $resp['Response'] ?? $resp['raw'] ?? $resp;
        // Forwards may be keyed as 'mail_forward_address' or 'mail_forward_adress' or similar.
        $found = $this->collectArraysWithKey($raw, 'mail_forward_address');
        if (empty($found)) {
            $found = $this->collectArraysWithKey($raw, 'mail_forward_adress');
        }
        if (empty($found)) {
            // Try fallback key
            $found = $this->collectArraysWithKey($raw, 'mail_forward');
        }

        return $found;
    }

    /* ----------------------------------------------------------------
     * Sync helpers (upsert into local tables)
     * ---------------------------------------------------------------- */

    /**
     * Upsert a single mailaccount found on KAS into local 'kas_mailaccounts' table.
     *
     * @param string $kasLogin
     * @param string $localPart  e.g. 'info'
     * @param string $domain     e.g. 'r3d.de'
     * @return bool true if upserted/found, false otherwise
     */
    public function syncMailAccount(string $kasLogin, string $localPart, string $domain): bool
    {
        try {
            $client = KasClient::where('account_login', $kasLogin)->first();
            if (!$client) {
                Log::warning("syncMailAccount: unknown KasClient {$kasLogin}");
                return false;
            }

            $accounts = $this->fetchMailaccounts($kasLogin);
            if (empty($accounts)) {
                Log::info("syncMailAccount: no accounts returned for {$kasLogin}");
                return false;
            }

            $email = strtolower($localPart . '@' . $domain);

            // find best matching account
            $found = null;
            foreach ($accounts as $acct) {
                // normalized keys used by KAS may vary: check both mail_login and addresses
                $login = strtolower($acct['mail_login'] ?? ($acct['mail_login'] ?? ''));
                $addrs = strtolower($acct['mail_addresses'] ?? $acct['mail_adresses'] ?? ($acct['mail_adress'] ?? ''));
                if ($login === '') {
                    // try match by address
                    if ($addrs !== '' && stripos($addrs, $email) !== false) {
                        $found = $acct;
                        break;
                    }
                    continue;
                }
                // match by internal mail_login or by full address
                if ($login === strtolower($acct['mail_login'])) {
                    // if mail_login matches the pattern "m07a2f99" we still need check addresses
                }
                if (strcasecmp($acct['mail_login'] ?? '', '') !== 0) {
                    // if mail_login maps to some random id, still consider address match
                    if (isset($acct['mail_addresses']) && stripos($acct['mail_addresses'], $email) !== false) {
                        $found = $acct;
                        break;
                    }
                }
                // also try mail_login equals localPart or mail_adresses contains the address
                if (strcasecmp($acct['mail_login'] ?? '', $localPart) === 0 || stripos($addrs, $email) !== false) {
                    $found = $acct;
                    break;
                }
            }

            if (!$found) {
                Log::info("syncMailAccount: account not found for {$email} on {$kasLogin}");
                return false;
            }

            // prepare upsert row
            $mailLogin = $found['mail_login'] ?? $localPart;
            $address = $found['mail_addresses'] ?? $found['mail_adresses'] ?? ($localPart . '@' . $domain);
            $status = (isset($found['mail_is_active']) && (strtoupper($found['mail_is_active']) === 'Y')) ? 'active' : 'missing';

            $now = now();

            // Upsert by kas_login + mail_login
            DB::table('kas_mailaccounts')->updateOrInsert(
                ['kas_login' => $kasLogin, 'mail_login' => $mailLogin],
                [
                    'kas_login'   => $kasLogin,
                    'mail_login'  => $mailLogin,
                    'domain'      => $domain,
                    'email'       => $address,
                    'status'      => $status,
                    'data_json'   => json_encode($found, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'client_id'   => $client->id,
                    'updated_at'  => $now,
                    // do not override created_at if exists (updateOrInsert won't set created_at on update)
                    'created_at'  => $now,
                ]
            );

            return true;
        } catch (Throwable $e) {
            Log::error("syncMailAccount error: " . $e->getMessage(), ['kas_login' => $kasLogin, 'local' => $localPart, 'domain' => $domain]);
            return false;
        }
    }

    /**
     * Upsert a single mailforward found on KAS into local 'kas_mailforwards' table.
     *
     * @param string $kasLogin
     * @param string $localPart  local part of the source (e.g. 'kontakt')
     * @param string $domain
     * @return bool
     */
    public function syncMailForward(string $kasLogin, string $localPart, string $domain): bool
    {
        try {
            $client = KasClient::where('account_login', $kasLogin)->first();
            if (!$client) {
                Log::warning("syncMailForward: unknown KasClient {$kasLogin}");
                return false;
            }

            $forwards = $this->fetchMailforwards($kasLogin);
            if (empty($forwards)) {
                Log::info("syncMailForward: no forwards returned for {$kasLogin}");
                return false;
            }

            $address = strtolower($localPart . '@' . $domain);

            $found = null;
            foreach ($forwards as $fw) {
                $addr = strtolower($fw['mail_forward_address'] ?? $fw['mail_forward_adress'] ?? ($fw['mail_forward_adress'] ?? ''));
                $targets = $fw['mail_forward_targets'] ?? $fw['mail_forward_targets'] ?? ($fw['mail_forward_targets'] ?? '');
                if ($addr === $address || stripos($targets, $address) !== false) {
                    $found = $fw;
                    break;
                }
            }

            if (!$found) {
                Log::info("syncMailForward: forward not found for {$address} on {$kasLogin}");
                return false;
            }

            $forwardAddress = $found['mail_forward_address'] ?? $found['mail_forward_adress'] ?? $address;
            $targetsString = is_array($found['mail_forward_targets'] ?? null) ? implode(',', $found['mail_forward_targets']) : ($found['mail_forward_targets'] ?? '');

            $status = 'active';
            $now = now();

            DB::table('kas_mailforwards')->updateOrInsert(
                ['kas_login' => $kasLogin, 'mail_forward_address' => $forwardAddress],
                [
                    'kas_login'            => $kasLogin,
                    'mail_forward_address' => $forwardAddress,
                    'mail_forward_targets' => $targetsString,
                    'mail_forward_comment' => $found['mail_forward_comment'] ?? ($found['mail_forward_comment'] ?? ''),
                    'mail_forward_spamfilter' => $found['mail_forward_spamfilter'] ?? '',
                    'status'               => $status,
                    'data_json'            => json_encode($found, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'client_id'            => $client->id,
                    'updated_at'           => $now,
                    'created_at'           => $now,
                ]
            );

            return true;
        } catch (Throwable $e) {
            Log::error("syncMailForward error: " . $e->getMessage(), ['kas_login' => $kasLogin, 'local' => $localPart, 'domain' => $domain]);
            return false;
        }
    }

    /* ----------------------------------------------------------------
     * Internal helpers
     * ---------------------------------------------------------------- */

    /**
     * Recursively walk $data and collect arrays that contain $key.
     * Useful because KAS responses can be nested in several shapes.
     *
     * @param mixed  $data
     * @param string $key
     * @return array
     */
    protected function collectArraysWithKey(mixed $data, string $key): array
    {
        $out = [];

        if (is_array($data)) {
            // If this array has the key directly and looks like a record, include it
            if (array_key_exists($key, $data)) {
                $out[] = $data;
            }

            // If this is an indexed list of records, check children
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    // if the child itself contains $key, add its records
                    if (array_key_exists($key, $v)) {
                        $out[] = $v;
                    } else {
                        // otherwise recurse
                        $out = array_merge($out, $this->collectArraysWithKey($v, $key));
                    }
                }
            }
        }

        return $out;
    }
}
