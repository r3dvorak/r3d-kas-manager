<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.13.3-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * database\seeders\KasSubdomainsSeeder.php
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KasDomain;
use App\Models\KasSubdomain;

class KasSubdomainsSeeder extends Seeder
{
    public function run(): void
    {
        $ord   = KasDomain::where('domain_name', '0rd')->first();
        $sixr3 = KasDomain::where('domain_name', '6r3')->first();

        if (!$ord || !$sixr3) {
            $this->command->error('❌ Domains 0rd.de und 6r3.de müssen existieren (bitte KasDomainsSeeder ausführen).');
            return;
        }

        $subs = [
            // Subdomains für ord.de
            ['domain_id' => $ord->id, 'subdomain_name' => '1', 'subdomain_path' => '/1.0rd.de/', 'php_version' => '8.3'],

            // Subdomains für 6r3.de
            ['domain_id' => $sixr3->id, 'subdomain_name' => '1', 'subdomain_path' => '/1.6r3.de/', 'php_version' => '8.3'],
            ['domain_id' => $sixr3->id, 'subdomain_name' => '2', 'subdomain_path' => '/2.6r3.de/', 'php_version' => '8.3'],
            ['domain_id' => $sixr3->id, 'subdomain_name' => '3', 'subdomain_path' => '/3.6r3.de/', 'php_version' => '8.3'],
        ];

        foreach ($subs as $sub) {
            $domain = KasDomain::find($sub['domain_id']);
            if (!$domain) {
                $this->command->warn("⚠️ Domain-ID {$sub['domain_id']} nicht gefunden, Subdomain wird übersprungen.");
                continue;
            }

            $subdomainFull = "{$sub['subdomain_name']}.{$domain->domain_full}";

            KasSubdomain::updateOrCreate(
                [
                    'domain_id'       => $sub['domain_id'],
                    'subdomain_name'  => $sub['subdomain_name'],
                ],
                [
                    'kas_client_id'   => $domain->kas_client_id,
                    'subdomain_full'  => $subdomainFull,
                    'subdomain_path'  => $sub['subdomain_path'],
                    'php_version'     => $sub['php_version'],
                    'ssl_status'      => 1,
                    'active'          => 1,
                ]
            );
        }

        $this->command->info('✅ KAS Subdomains erfolgreich eingetragen (' . count($subs) . ')');
    }
}
