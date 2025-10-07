<?php

/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.2.0-alpha
 * @date      2025-09-24
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * Service to connect to the All-Inkl KAS SOAP API.
 */

namespace App\Services;

use SoapClient;
use SoapFault;

class KasApiService
{
    protected SoapClient $client;
    protected string $kasUser;
    protected string $kasPassword;

    public function __construct()
    {
        $wsdl = config('kas.wsdl');
        $this->kasUser = config('kas.user');
        $this->kasPassword = config('kas.password');

        try {
            $this->client = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);
        } catch (SoapFault $e) {
            throw new \RuntimeException("Failed to connect to KAS API: " . $e->getMessage());
        }
    }

    /**
     * Generic call wrapper for KAS API.
     */
    public function call(string $function, array $params = []): mixed
    {
        try {
            $auth = [
                'kas_login'    => $this->kasUser,
                'kas_password' => $this->kasPassword,
                'session_lifetime' => 600,
            ];

            $response = $this->client->__soapCall($function, [$auth + $params]);

            return $response;
        } catch (SoapFault $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
