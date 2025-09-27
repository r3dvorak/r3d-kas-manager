<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.7.4-alpha
 * @date      2025-09-27
 * 
 * @license   MIT License
 * @copyright (C) 2025
 * 
 * Seeder: Creates default Admin, KasClient and demo users.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\KasClient;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Beispiel KAS Client ---
        $kasClient = KasClient::updateOrCreate(
            ['login' => 'w01e77bc'],
            [
                'name'         => '000 R3D & Trimains',
                'email'        => 'faktura@r3d.de',
                'domain'       => '0rd.de',
                'api_user'     => 'w01e77bc',
                'api_password' => Hash::make('srrR3wo2qckkDEZRwkxq'),
                'role'         => 'client',
            ]
        );

        // --- Admin User ---
        User::updateOrCreate(
            ['email' => 'faktura@r3d.de'],
            [
                'name'          => 'Richard Dvořák',
                'login'         => 'RIIID',
                'password'      => Hash::make('Pood.2025'),
                'role'          => 'admin',
                'is_admin'      => 1,
                'kas_client_id' => $kasClient->id,
            ]
        );

        // --- Demo User ---
        User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name'          => 'Demo User',
                'login'         => 'demo',
                'password'      => Hash::make('password'),
                'role'          => 'user',
                'kas_client_id' => $kasClient->id,
            ]
        );

        // --- Client User ---
        User::updateOrCreate(
            ['email' => 'client@example.com'],
            [
                'name'          => 'Client User',
                'login'         => 'client1',
                'password'      => Hash::make('test123'),
                'role'          => 'client',
                'kas_client_id' => $kasClient->id,
            ]
        );
    }
}
