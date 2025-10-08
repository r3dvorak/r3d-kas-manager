<?php
/**
 * R3D KAS Manager – Import Mail Accounts from JSON
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.19.3-alpha
 * @date      2025-10-08
 * @license   MIT License
 *
 * Imports all mailbox data from storage/kas_responses/get_mailaccounts_all.json
 * into the kas_mailaccounts database table.
 * Automatically links entries to kas_domains and kas_clients
 * based on domain name and KAS login.
 * Supports optional --truncate flag for clean re-imports.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class KasImportMailaccounts extends Command
{
    protected $signature = 'kas:import-mailaccounts {--truncate : Empty the table before import}';
    protected $description = '0.19.3-alpha - Imports KAS mail account data from get_mailaccounts_all.json into kas_mailaccounts table, linking to kas_domains and kas_clients.';

    public function handle(): void
    {
        $jsonPath = storage_path('kas_responses/get_mailaccounts_all.json');

        if (!File::exists($jsonPath)) {
            $this->error("File not found: {$jsonPath}");
            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        if (!$data || !is_array($data)) {
            $this->error("Invalid JSON format or empty file.");
            return;
        }

        if ($this->option('truncate')) {
            DB::table('kas_mailaccounts')->truncate();
            $this->warn('Table kas_mailaccounts truncated.');
        }

        // Preload domain + client mappings
        $domains = DB::table('kas_domains')->pluck('id', 'domain_full')->toArray();
        $clients = DB::table('kas_clients')->pluck('id', 'account_login')->toArray();

        $insertCount = 0;
        $missingCount = 0;
        $linkedDomains = 0;
        $linkedClients = 0;

        foreach ($data as $kasLogin => $mailboxes) {
            foreach ($mailboxes as $mailLogin => $info) {
                $domainName = $info['domain'] ?? null;
                $domainId = $domainName && isset($domains[$domainName]) ? $domains[$domainName] : null;
                if ($domainId) $linkedDomains++;

                $clientId = isset($clients[$kasLogin]) ? $clients[$kasLogin] : null;
                if ($clientId) $linkedClients++;

                $record = [
                    'kas_login'   => $kasLogin,
                    'mail_login'  => $mailLogin,
                    'domain'      => $domainName,
                    'email'       => $info['email'] ?? null,
                    'domain_id'   => $domainId,
                    'client_id'   => $clientId,
                    'status'      => empty($info['data']) ? 'missing' : 'active',
                    'data_json'   => json_encode($info['data'] ?? []),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];

                DB::table('kas_mailaccounts')->insert($record);

                if ($record['status'] === 'missing') {
                    $missingCount++;
                } else {
                    $insertCount++;
                }
            }
        }

        $this->info("✅ Import finished.");
        $this->line("Active entries: {$insertCount}");
        $this->line("Missing entries: {$missingCount}");
        $this->line("Linked domains: {$linkedDomains}");
        $this->line("Linked clients: {$linkedClients}");
        $this->line("Total processed: " . ($insertCount + $missingCount));
    }
}
