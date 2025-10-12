<?php
/**
 * R3D KAS Manager – KAS API Gateway (SOAP JSON wrapper)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.4-alpha
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

    public function callForLogin(string $kasLogin, string $action, array $params = []): array
    {
        $client = KasClient::where('account_login', $kasLogin)->firstOrFail();
        return $this->call($kasLogin, $client->account_password, $action, $params);
    }

    public function fetchMailaccounts(string $kasLogin): array
    {
        $resp = $this->callForLogin($kasLogin, 'get_mailaccounts', []);
        if (!($resp['success'] ?? false)) {
            Log::warning("fetchMailaccounts: KAS returned no success for {$kasLogin}", $resp);
            return [];
        }
        $raw = $resp['Response'] ?? $resp['raw'] ?? $resp;
        return $this->collectArraysWithKey($raw, 'mail_login');
    }

    public function fetchMailforwards(string $kasLogin): array
    {
        $resp = $this->callForLogin($kasLogin, 'get_mailforwards', []);
        if (!($resp['success'] ?? false)) {
            Log::warning("fetchMailforwards: KAS returned no success for {$kasLogin}", $resp);
            return [];
        }
        $raw = $resp['Response'] ?? $resp['raw'] ?? $resp;
        $found = $this->collectArraysWithKey($raw, 'mail_forward_address');
        if (empty($found)) $found = $this->collectArraysWithKey($raw, 'mail_forward_adress');
        if (empty($found)) $found = $this->collectArraysWithKey($raw, 'mail_forward');
        return $found;
    }

    /**
     * Upsert a single mailaccount and return the local id (int) on success or false.
     */
    public function syncMailAccount(string $kasLogin, string $localPart, string $domain)
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
            $found = null;
            foreach ($accounts as $acct) {
                $addrs = strtolower($acct['mail_addresses'] ?? $acct['mail_adresses'] ?? '');
                $mlogin = strtolower($acct['mail_login'] ?? '');
                if ($mlogin === strtolower($localPart) || stripos($addrs, $email) !== false) {
                    $found = $acct;
                    break;
                }
            }

            if (!$found) {
                Log::info("syncMailAccount: account not found for {$email} on {$kasLogin}");
                return false;
            }

            $mailLogin = $found['mail_login'] ?? $localPart;
            $address = $found['mail_addresses'] ?? $found['mail_adresses'] ?? ($localPart . '@' . $domain);
            $status = (isset($found['mail_is_active']) && strtoupper($found['mail_is_active']) === 'Y') ? 'active' : 'missing';
            $now = now();

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
                    'created_at'  => $now,
                ]
            );

            $id = DB::table('kas_mailaccounts')->where('kas_login', $kasLogin)->where('mail_login', $mailLogin)->value('id');
            return $id ?: false;
        } catch (Throwable $e) {
            Log::error("syncMailAccount error: " . $e->getMessage(), ['kas_login' => $kasLogin, 'local' => $localPart, 'domain' => $domain]);
            return false;
        }
    }

    /**
     * Upsert a single mailforward and return the local id (int) on success or false.
     */
    public function syncMailForward(string $kasLogin, string $localPart, string $domain)
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
                $addr = strtolower($fw['mail_forward_address'] ?? $fw['mail_forward_adress'] ?? '');
                $targets = strtolower(is_array($fw['mail_forward_targets'] ?? null) ? implode(',', $fw['mail_forward_targets']) : ($fw['mail_forward_targets'] ?? ''));
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
                    'mail_forward_comment' => $found['mail_forward_comment'] ?? '',
                    'mail_forward_spamfilter' => $found['mail_forward_spamfilter'] ?? '',
                    'status'               => $status,
                    'data_json'            => json_encode($found, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'client_id'            => $client->id,
                    'updated_at'           => $now,
                    'created_at'           => $now,
                ]
            );

            $id = DB::table('kas_mailforwards')->where('kas_login', $kasLogin)->where('mail_forward_address', $forwardAddress)->value('id');
            return $id ?: false;
        } catch (Throwable $e) {
            Log::error("syncMailForward error: " . $e->getMessage(), ['kas_login' => $kasLogin, 'local' => $localPart, 'domain' => $domain]);
            return false;
        }
    }

    protected function collectArraysWithKey($data, string $key): array
    {
        $out = [];
        if (is_array($data)) {
            if (array_key_exists($key, $data)) {
                $out[] = $data;
            }
            foreach ($data as $v) {
                if (is_array($v)) {
                    if (array_key_exists($key, $v)) {
                        $out[] = $v;
                    } else {
                        $out = array_merge($out, $this->collectArraysWithKey($v, $key));
                    }
                }
            }
        }
        return $out;
    }
}
