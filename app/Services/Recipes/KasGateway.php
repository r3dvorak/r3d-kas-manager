<?php
/**
 * R3D KAS Manager – KAS API Gateway (SOAP JSON wrapper)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.0-alpha
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
use SoapClient;

class KasGateway
{
    /** Low-level call: requires login + (decrypted) password string. */
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
            'kas_auth_data'    => $password,          // pass decrypted value here
            'kas_action'       => $action,
            'KasRequestParams' => $params,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $raw = $soap->__soapCall('KasApi', [$json]);

            // Normalize
            if (is_string($raw)) {
                $d = json_decode($raw, true);
                $raw = (json_last_error() === JSON_ERROR_NONE && is_array($d))
                    ? $d
                    : ['Response' => ['Raw' => $raw]];
            } elseif (is_object($raw)) {
                $raw = json_decode(json_encode($raw), true) ?? [];
            } elseif (!is_array($raw)) {
                $raw = ['Response' => ['Raw' => $raw]];
            }

            $resp = $raw['Response'] ?? $raw;
            $ok   = (string)($resp['ReturnString'] ?? $raw['ReturnString'] ?? 'TRUE') === 'TRUE';

            return ['success' => $ok, 'Response' => $resp];
        } catch (\SoapFault $e) {
            $msg = $e->faultstring ?? $e->getMessage();

            // Flood protection retry
            if (stripos($msg, 'flood_protection') !== false) {
                sleep(2);
                $raw = $soap->__soapCall('KasApi', [$json]);
                $raw = is_object($raw) ? json_decode(json_encode($raw), true) : (array)$raw;
                return ['success' => true, 'Response' => ($raw['Response'] ?? $raw)];
            }

            return ['success' => false, 'error' => "KAS SOAP error: {$msg}"];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => "KAS SOAP error: ".$e->getMessage()];
        }
    }

    /** Convenience: resolves KasClient by login and calls using its stored password. */
    public function callForLogin(string $kasLogin, string $action, array $params = []): array
    {
        $client = KasClient::where('account_login', $kasLogin)->firstOrFail();

        // If the model stores encrypted value, expose a decrypted accessor like:
        // public function getAccountPasswordAttribute($v){ return Crypt::decryptString($v); }
        // so we can pass $client->account_password directly here.
        return $this->call($kasLogin, $client->account_password, $action, $params);
    }
}
