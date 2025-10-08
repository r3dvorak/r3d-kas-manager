<?php
/**
 * R3D KAS Manager – Import Mailforwards from JSON
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.20.6-alpha
 * @date      2025-10-08
 * @license   MIT License
 *
 * Imports all mail forwarder data from
 * storage/kas_responses/get_mailforwards_all.json
 * into the kas_mailforwards database table.
 * Automatically links each entry to kas_domains and kas_clients
 * based on domain name and KAS login.
 * Supports optional --truncate flag for clean re-imports.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class KasImportMailforwards extends Command
{
    protected $signature = 'kas:import-mailforwards {--truncate : Empty the table before import}';
    protected $description = 'Imports KAS mail forwarder data from get_mailforwards_all.json into kas_mailforwards table.';

    public function handle(): void
    {
        $jsonPath = storage_path('kas_responses/get_mailforwards_all.json');

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
            DB::table('kas_mailforwards')->truncate();
            $this->warn('Table kas_mailforwards truncated.');
        }

        // Preload domain + client mappings
        $domains = DB::table('kas_domains')->pluck('id', 'domain_full')->toArray();
        $clients = DB::table('kas_clients')->pluck('id', 'account_login')->toArray();

        $insertCount = 0;
        $missingCount = 0;
        $linkedDomains = 0;
        $linkedClients = 0;

        foreach ($data as $kasLogin => $forwarders) {
            $clientId = $clients[$kasLogin] ?? null;
            if ($clientId) $linkedClients++;

            // Empty or missing list
            if (empty($forwarders)) {
                DB::table('kas_mailforwards')->insert([
                    'kas_login'      => $kasLogin,
                    'mail_forward_address' => null,
                    'mail_forward_targets'  => null,
                    'mail_forward_comment'  => null,
                    'mail_forward_spamfilter' => null,
                    'in_progress'    => false,
                    'status'         => 'missing',
                    'client_id'      => $clientId,
                    'data_json'      => json_encode([]),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                $missingCount++;
                continue;
            }

            foreach ($forwarders as $fwd) {
                $src = $fwd['mail_forward_address'] ?? $fwd['mail_forward_adress'] ?? null;
                $targets = $fwd['mail_forward_targets'] ?? null;
                $comment = $fwd['mail_forward_comment'] ?? null;
                $spam = $fwd['mail_forward_spamfilter'] ?? null;
                $inProg = isset($fwd['in_progress']) && strtoupper($fwd['in_progress']) === 'TRUE';

                // Extract domain from address
                $domainName = null;
                if ($src && str_contains($src, '@')) {
                    [$user, $domainName] = explode('@', $src, 2);
                }

                $domainId = $domainName && isset($domains[$domainName]) ? $domains[$domainName] : null;
                if ($domainId) $linkedDomains++;

                DB::table('kas_mailforwards')->insert([
                    'kas_login'               => $kasLogin,
                    'mail_forward_address'    => $src,
                    'mail_forward_targets'    => $targets,
                    'mail_forward_comment'    => $comment,
                    'mail_forward_spamfilter' => $spam,
                    'in_progress'             => $inProg,
                    'status'                  => 'active',
                    'domain_id'               => $domainId,
                    'client_id'               => $clientId,
                    'data_json'               => json_encode($fwd),
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);

                $insertCount++;
            }
        }

        $this->info("✅ Import finished.");
        $this->line("Active entries: {$insertCount}");
        $this->line("Missing accounts: {$missingCount}");
        $this->line("Linked domains: {$linkedDomains}");
        $this->line("Linked clients: {$linkedClients}");
        $this->line("Total processed: " . ($insertCount + $missingCount));
    }
}
