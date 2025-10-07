<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.2.5-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * SOAP client wrapper for the All-Inkl KAS API.
 */

namespace App\Services;

use SoapClient;
use SoapFault;
use Illuminate\Support\Facades\Log;

class KasApiClient
{
    protected SoapClient $client;
    protected string $kasUser;
    protected string $kasPassword;

    public function __construct()
    {
        $wsdl = config('services.kas.wsdl', env('KAS_WSDL'));
        $this->kasUser = config('services.kas.user', env('KAS_USER'));
        $this->kasPassword = config('services.kas.password', env('KAS_PASSWORD'));

        try {
            $this->client = new SoapClient($wsdl, [
                'trace'      => 1,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);
        } catch (SoapFault $e) {
            Log::error("KAS API SOAP init failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Call a KAS API function with automatic authentication.
     *
     * @param string $function KAS API function name
     * @param array  $params   Parameters for the call
     * @return array
     */
    public function request(string $function, array $params = []): array
    {
        $auth = [
            'kas_login'    => $this->kasUser,
            'kas_password' => $this->kasPassword,
        ];

        $request = array_merge($auth, $params);

        try {
            $result = $this->client->__soapCall($function, [$request]);

            // Normalize response to array
            $data = json_decode(json_encode($result), true);

            Log::info("KAS API call success: {$function}", [
                'params' => $params,
                'result' => $data,
            ]);

            return $data ?? [];
        } catch (SoapFault $e) {
            Log::error("KAS API call failed: {$function}", [
                'params' => $params,
                'error'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
