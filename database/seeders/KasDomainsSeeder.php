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
 * database\seeders\KasDomainsSeeder.php
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KasClient;
use App\Models\KasDomain;

class KasDomainsSeeder extends Seeder
{
    public function run(): void
    {
        $client = KasClient::where('login', 'w01e77bc')->first();

        if (!$client) {
            $this->command->error('❌ Kein KAS-Client mit Login "w01e77bc" gefunden – bitte DatabaseSeeder ausführen!');
            return;
        }

        $domains = [
            ['domain_name' => '0rd',  'domain_tld' => 'de', 'domain_path' => '/0rd.de/',  'php_version' => '8.1'],
            ['domain_name' => '6r3',  'domain_tld' => 'de', 'domain_path' => '/6r3.de/',  'php_version' => '8.3'],
            ['domain_name' => '6rd',  'domain_tld' => 'de', 'domain_path' => '/6rd.de/',  'php_version' => '8.1'],
            ['domain_name' => '8r3',  'domain_tld' => 'de', 'domain_path' => '/8r3.de/',  'php_version' => '8.1'],
            ['domain_name' => '8rd',  'domain_tld' => 'de', 'domain_path' => '/8rd.de/',  'php_version' => '8.1'],
            ['domain_name' => '9r3',  'domain_tld' => 'de', 'domain_path' => '/test/',    'php_version' => '8.3'],
            ['domain_name' => '9rd',  'domain_tld' => 'de', 'domain_path' => '/9rd.de/',  'php_version' => '8.1'],
        ];

        foreach ($domains as $domain) {
            KasDomain::updateOrCreate(
                [
                    'domain_name'   => $domain['domain_name'],
                    'domain_tld'    => $domain['domain_tld'],
                    'kas_client_id' => $client->id,
                ],
                [
                    'domain_full'     => $domain['domain_name'] . '.' . $domain['domain_tld'],
                    'domain_path'     => $domain['domain_path'],
                    'php_version'     => $domain['php_version'],
                    'redirect_status' => 0,
                    'ssl_status'      => 1,
                    'active'          => 1,
                ]
            );
        }

        $this->command->info('✅ KAS Domains erfolgreich eingetragen (' . count($domains) . ')');
    }
}
