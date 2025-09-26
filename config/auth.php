<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.6.7-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 */

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // Neuer Guard für KAS Clients (eigene Session)
        'kas_client' => [
            'driver'   => 'session',
            'provider' => 'kas_clients',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        // Provider für KAS Clients
        'kas_clients' => [
            'driver' => 'eloquent',
            'model'  => App\Models\KasClient::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],

        // Falls du später auch Password-Resets für Clients brauchst
        'kas_clients' => [
            'provider' => 'kas_clients',
            'table'    => 'kas_client_password_resets', // optional, erstelle Tabelle wenn nötig
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
