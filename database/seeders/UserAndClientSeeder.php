<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.7.3-alpha
 * @date      2025-09-27
 * 
 * @license   MIT License
 * @copyright (C) 2025
 * 
 * database\seeders\UserAndClientSeeder.php
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
        User::updateOrCreate(
            ['email' => 'faktura@r3d.de'],
            [
                'name'     => 'Richard Dvořák',
                'login'    => 'RIID', // required, users table hat login!
                'password' => Hash::make('Pood.2025'),
                'role'     => 'admin',
                'is_admin' => 1,
            ]
        );

        // Example KAS client
        $kasClient = KasClient::updateOrCreate(
            ['login' => 'w01e77bc'],
            [
                'name'         => '000 R3D & Trimains',
                'email'        => 'faktura@r3d.de',
                'domain'       => '0rd.de',
                'api_user'     => 'w01e77bc',
                'api_password' => Hash::make('srrR3wo2qckkDEZRwkxq'), // oder plain, je nach Bedarf
                'role'         => 'client',
            ]
        );

        // Client user bound to this client
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
