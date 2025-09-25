<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.0-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Seeder: Creates default admin + demo users.
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
        // Erst einen KAS-Client anlegen
        $kasClient = KasClient::create([
            'name'         => 'KAS Client 1',
            'login'        => 'w01e77bc',
            'domain'       => '0rd.de',
            'api_password' => Hash::make('srrR3wo2qckkDEZRwkxq'),
            'api_url'      => 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl',
        ]);

        // Admin-User mit Bezug zu KAS-Client
        User::create([
            'name'          => 'Richard Dvorak',
            'login'         => 'RIIID', // wichtig: wir nutzen jetzt login statt email
            'email'         => 'faktura@r3d.de', // falls du es behalten willst
            'password'      => Hash::make('Pood.2025'),
            'role'          => 'admin',
            'kas_client_id' => $kasClient->id,
        ]);

        // Optional: Demo-User ohne Admin
        User::create([
            'name'          => 'Demo User',
            'login'         => 'demo',
            'email'         => 'demo@example.com',
            'password'      => Hash::make('password'),
            'role'          => 'user',
            'kas_client_id' => $kasClient->id,
        ]);
    }
}
