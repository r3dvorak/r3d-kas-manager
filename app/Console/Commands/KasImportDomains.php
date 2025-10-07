<?php
/**
 * R3D KAS Manager – Import All-Inkl Domains
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.16.8-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * Imports all KAS domains from local JSON (get_domains_all.json),
 * maintaining chronological order (oldest clients first).
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\KasDomain;
use App\Models\KasClient;
use Throwable;

class KasImportDomains extends Command
{
    protected $signature = 'kas:import-domains 
                            {--fresh : Truncate kas_domains table before import}
                            {--source= : Optional JSON source file (default: storage/kas_responses/get_domains_all.json)}';

    protected $description = 'Imports all KAS domains (from JSON) into kas_domains table, preserving client order.';

    public function handle(): int
    {
        $source = $this->option('source') ?? storage_path('kas_responses/get_domains_all.json');
        $this->info("🔎 Starting domain import from {$source}");

        if (!file_exists($source)) {
            $this->error("❌ File not found: {$source}");
            return Command::FAILURE;
        }

        $json = json_decode(file_get_contents($source), true);
        if (!$json) {
            $this->error('❌ Invalid or empty JSON file.');
            return Command::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('⚠️ Truncating kas_domains table...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('kas_domains')->truncate();
            DB::statement('ALTER TABLE kas_domains AUTO_INCREMENT = 1;');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('✅ kas_domains table truncated and reset.');
        }

        // Get clients sorted by account_login ascending
        $clients = KasClient::orderBy('account_login', 'asc')->get();
        $totalClients = $clients->count();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $totalDomains = 0;

        foreach ($clients as $index => $client) {
            $login = $client->account_login;
            $entry = $json[$login] ?? null;

            if (!$entry || empty($entry['domains'])) {
                $this->warn(sprintf("[%02d/%d] %s — no domains", $index + 1, $totalClients, $login));
                continue;
            }

            // Sort domain list by domain_name
            $domains = collect($entry['domains'])->sortBy('domain_name')->values()->all();

            $this->line(sprintf("📂 [%02d/%d] %s: %d domain(s)", $index + 1, $totalClients, $login, count($domains)));
            $totalDomains += count($domains);

            foreach ($domains as $dom) {
                $domain_name = $dom['domain_name'] ?? null;
                if (!$domain_name) {
                    $skipped++;
                    continue;
                }

                // split domain.tld
                $parts = explode('.', $domain_name);
                $domain_tld = array_pop($parts);
                $domain_short = implode('.', $parts);
                $domain_full = $domain_name;

                $record = [
                    'kas_client_id'   => $client->id,
                    'domain_name'     => $domain_short,
                    'domain_tld'      => $domain_tld,
                    'domain_full'     => $domain_full,
                    'domain_path'     => $dom['domain_path'] ?? '/',
                    'php_version'     => $dom['php_version'] ?? null,
                    'redirect_status' => (int)($dom['domain_redirect_status'] ?? 0),
                    'redirect_target' => $dom['domain_path'] ?? null,
                    'ssl_status'      => ($dom['ssl_certificate_sni_is_active'] ?? 'n') === 'j' ? 1 : 0,
                    'active'          => ($dom['is_active'] ?? 'N') === 'Y' ? 1 : 0,
                ];

                $existing = KasDomain::where('domain_full', $domain_full)->first();

                if ($existing) {
                    $existing->update($record);
                    $updated++;
                } else {
                    KasDomain::create($record);
                    $created++;
                }
            }
        }

        $this->newLine();
        $this->info('✅ Domain import complete!');
        $this->line("   Clients processed:  {$totalClients}");
        $this->line("   Domains processed:  {$totalDomains}");
        $this->line("   Created new:        {$created}");
        $this->line("   Updated existing:   {$updated}");
        $this->line("   Skipped:            {$skipped}");

        return Command::SUCCESS;
    }
}
