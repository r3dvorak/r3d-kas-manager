<?php
/**
 * R3D KAS Manager – Auth Configuration
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.12.1-alpha
 * @date      2025-10-05
 *
 * @copyright (C) 2025
 * @license   MIT License
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Authentication Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | "web"  → Admin users (App\Models\User)
    | "kas_client" → Hosting clients (App\Models\KasClient)
    |
    */
    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        'kas_client' => [
            'driver'   => 'session',
            'provider' => 'kas_clients',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        'kas_clients' => [
            'driver' => 'eloquent',
            'model'  => App\Models\KasClient::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Settings
    |--------------------------------------------------------------------------
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],

        'kas_clients' => [
            'provider' => 'kas_clients',
            'table'    => 'kas_client_password_resets', // optional table
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */
    'password_timeout' => 10800,
];
