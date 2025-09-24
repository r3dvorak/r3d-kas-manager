<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.5.0-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Seeds Admin + Client accounts for testing.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\KasClient;

class UserAndClientSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name'     => 'RIIID',
            'email'    => 'admin@example.com',
            'password' => Hash::make('Pood.2025'),
            'role'     => 'admin',
        ]);

        // Example KAS client
        $kasClient = KasClient::create([
            'name'         => 'Main Client',
            'kas_login'    => 'w01e77bc',
            'kas_auth_data'=> 'srrR3wo2qckkDEZRwkxq', // TODO: replace with real
        ]);

        // Client user bound to this client
        User::create([
            'name'          => 'Client User',
            'email'         => 'client@example.com',
            'password'      => Hash::make('test123'),
            'role'          => 'client',
            'kas_client_id' => $kasClient->id,
        ]);
    }
}
