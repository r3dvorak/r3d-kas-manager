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

return [
    'wsdl' => env('KAS_WSDL', 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl'),
    'user' => env('KAS_USER'),
    'password' => env('KAS_PASSWORD'),
];
