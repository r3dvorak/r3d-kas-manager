<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.14.0-alpha
 * @date      2025-10-05
 * 
 * @license   MIT License
 * @copyright (C) 2025
 * 
 * Seeder: Creates default Admin, KasClient, and demo domains/subdomains.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\KasClient;
use App\Models\KasDomain;
use App\Models\KasSubdomain;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Beispiel KAS Client (w01e77bc) ---
        $kasClient = KasClient::updateOrCreate(
            ['login' => 'w01e77bc'],
            [
                'name'         => '000 R3D & Trimains',
                'email'        => 'faktura@r3d.de',
                'api_user'     => 'w01e77bc',
                'password'     => Hash::make('srrR3wo2qckkDEZRwkxq'),
                'api_password' => 'srrR3wo2qckkDEZRwkxq',
                'role'         => 'client',
            ]
        );

        // --- Admin User ---
        $admin = User::updateOrCreate(
            ['email' => 'faktura@r3d.de'],
            [
                'name'          => 'Richard Dvořák',
                'login'         => 'RIIID',
                'password'      => Hash::make('Pood.2025'),
                'role'          => 'admin',
                'is_admin'      => 1,
            ]
        );

        // --- Domains für diesen Client ---
        $ord = KasDomain::updateOrCreate(
            ['domain_full' => '0rd.de'],
            [
                'domain_name'   => '0rd',
                'domain_tld'    => 'de',
                'domain_path'   => '/0rd.de/',
                'php_version'   => '8.1',
                'redirect_status' => 0,
                'ssl_status'    => 1,
                'active'        => 1,
                'kas_client_id' => $kasClient->id,
            ]
        );

        $sixr3 = KasDomain::updateOrCreate(
            ['domain_full' => '6r3.de'],
            [
                'domain_name'   => '6r3',
                'domain_tld'    => 'de',
                'domain_path'   => '/6r3.de/',
                'php_version'   => '8.3',
                'redirect_status' => 0,
                'ssl_status'    => 1,
                'active'        => 1,
                'kas_client_id' => $kasClient->id,
            ]
        );

        // --- Subdomains ---
        KasSubdomain::updateOrCreate(
            ['subdomain_full' => '1.0rd.de'],
            [
                'subdomain_name' => '1',
                'subdomain_path' => '/1.0rd.de/',
                'php_version'    => '8.3',
                'ssl_status'     => 1,
                'active'         => 1,
                'domain_id'      => $ord->id,
                'kas_client_id'  => $kasClient->id,
            ]
        );

        KasSubdomain::updateOrCreate(
            ['subdomain_full' => '1.6r3.de'],
            [
                'subdomain_name' => '1',
                'subdomain_path' => '/1.6r3.de/',
                'php_version'    => '8.3',
                'ssl_status'     => 1,
                'active'         => 1,
                'domain_id'      => $sixr3->id,
                'kas_client_id'  => $kasClient->id,
            ]
        );

        KasSubdomain::updateOrCreate(
            ['subdomain_full' => '2.6r3.de'],
            [
                'subdomain_name' => '2',
                'subdomain_path' => '/2.6r3.de/',
                'php_version'    => '8.3',
                'ssl_status'     => 1,
                'active'         => 1,
                'domain_id'      => $sixr3->id,
                'kas_client_id'  => $kasClient->id,
            ]
        );

        $this->command->info('✅ Admin, KasClient, Domains & Subdomains seeded successfully.');
    }
}
