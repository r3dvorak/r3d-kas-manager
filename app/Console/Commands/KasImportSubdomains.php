<?php
/**
 * R3D KAS Manager – Import All-Inkl Subdomains
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.17.6-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * Imports all KAS subdomains from local JSON (get_subdomains_all.json),
 * preserving chronological order (oldest clients first).
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\KasSubdomain;
use App\Models\KasClient;
use App\Models\KasDomain;
use Throwable;

class KasImportSubdomains extends Command
{
    protected $signature = 'kas:import-subdomains 
                            {--fresh : Truncate kas_subdomains table before import}
                            {--source= : Optional JSON source file (default: storage/kas_responses/get_subdomains_all.json)}';

    protected $description = 'Imports all KAS subdomains (from JSON) into kas_subdomains table, preserving client order.';

    public function handle(): int
    {
        $source = $this->option('source') ?? storage_path('kas_responses/get_subdomains_all.json');
        $this->info("🔎 Starting subdomain import from {$source}");

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
            $this->warn('⚠️ Truncating kas_subdomains table...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('kas_subdomains')->truncate();
            DB::statement('ALTER TABLE kas_subdomains AUTO_INCREMENT = 1;');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('✅ kas_subdomains table truncated and reset.');
        }

        $clients = KasClient::orderBy('account_login', 'asc')->get();
        $totalClients = $clients->count();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $totalSubdomains = 0;

        foreach ($clients as $index => $client) {
            $login = $client->account_login;
            $entry = $json[$login] ?? null;

            if (!$entry || empty($entry['subdomains'])) {
                $this->warn(sprintf("[%02d/%d] %s — no subdomains", $index + 1, $totalClients, $login));
                continue;
            }

            $subdomains = collect($entry['subdomains'])
                ->sortBy('subdomain_name')
                ->values()
                ->all();

            $this->line(sprintf("📂 [%02d/%d] %s: %d subdomain(s)", $index + 1, $totalClients, $login, count($subdomains)));
            $totalSubdomains += count($subdomains);

            foreach ($subdomains as $sub) {
                $fqdn = $sub['subdomain_name'] ?? null;
                if (!$fqdn) {
                    $skipped++;
                    continue;
                }

                // Split FQDN into subdomain + domain
                if (str_contains($fqdn, '.')) {
                    [$subdomain_name, $domain_name] = explode('.', $fqdn, 2);
                } else {
                    $subdomain_name = $fqdn;
                    $domain_name = null;
                }

                if (!$domain_name) {
                    $this->warn("⚠️ Skipping {$fqdn} (could not extract domain name)");
                    $skipped++;
                    continue;
                }

                // find matching domain record (safe)
                $domain = KasDomain::where('domain_full', $domain_name)
                    ->where('kas_client_id', $client->id)
                    ->first();

                if (!$domain) {
                    $this->warn("⚠️ Skipping {$fqdn} (no parent domain found)");
                    $skipped++;
                    continue;
                }

                $record = [
                    'kas_client_id'   => $client->id,
                    'domain_id'       => $domain->id,
                    'subdomain_name'  => $subdomain_name,
                    'subdomain_full'  => $fqdn,
                    'subdomain_path'  => $sub['subdomain_path'] ?? null,
                    'php_version'     => $sub['php_version'] ?? null,
                    'ssl_status'      => ($sub['ssl_certificate_sni_is_active'] ?? 'n') === 'j' ? 1 : 0,
                    'active'          => ($sub['is_active'] ?? 'N') === 'Y' ? 1 : 0,
                ];

                $existing = KasSubdomain::where('subdomain_full', $fqdn)->first();

                if ($existing) {
                    $existing->update($record);
                    $updated++;
                } else {
                    KasSubdomain::create($record);
                    $created++;
                }
            }
        }

        $this->newLine();
        $this->info('✅ Subdomain import complete!');
        $this->line("   Clients processed:   {$totalClients}");
        $this->line("   Subdomains processed: {$totalSubdomains}");
        $this->line("   Created new:          {$created}");
        $this->line("   Updated existing:     {$updated}");
        $this->line("   Skipped:              {$skipped}");

        return Command::SUCCESS;
    }
}
