<?php
/**
 * R3D KAS Manager – Import DNS Records from get_dns_all.json
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.23.4-alpha
 * @date      2025-10-08
 * @license   MIT License
 *
 * Imports DNS zone data from storage/kas_responses/get_dns_all.json
 * into the kas_dns_records table.
 * Links to kas_domains via domain_id, and aligns kas_login automatically
 * with the owning client (via kas_clients.account_login).
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\KasDnsRecord;
use App\Models\KasDomain;

class KasImportDns extends Command
{
    protected $signature = 'kas:import-dns {--truncate}';
    protected $description = 'Import DNS records from get_dns_all.json into kas_dns_records and align kas_login with owning client.';

    public function handle()
    {
        $path = base_path('storage/kas_responses/get_dns_all.json');

        if (!file_exists($path)) {
            $this->error("Missing file: {$path}");
            return 1;
        }

        $json = json_decode(file_get_contents($path), true);
        if (!$json || !is_array($json)) {
            $this->error('Invalid or empty JSON.');
            return 1;
        }

        if ($this->option('truncate')) {
            DB::table('kas_dns_records')->truncate();
            $this->info('Table kas_dns_records truncated.');
        }

        $domains = KasDomain::all(['id', 'domain_full']);
        $map = $domains->pluck('id', 'domain_full')->toArray();

        $inserted = 0;

        foreach ($json as $domainName => $records) {
            $domainId = $map[$domainName] ?? null;

            if (!$domainId) {
                $this->warn("⚠ Domain not found in DB: {$domainName}");
                continue;
            }

            foreach ($records as $r) {
                KasDnsRecord::create([
                    'domain_id'         => $domainId,
                    'kas_login'         => null, // will be aligned later
                    'record_zone'       => $r['record_zone'] ?? '',
                    'record_name'       => $r['record_name'] ?? '',
                    'record_type'       => $r['record_type'] ?? '',
                    'record_data'       => $r['record_data'] ?? '',
                    'record_aux'        => $r['record_aux'] ?? 0,
                    'record_id_kas'     => $r['record_id'] ?? null,
                    'record_changeable' => $r['record_changeable'] ?? 'N',
                    'record_deleteable' => $r['record_deleteable'] ?? 'N',
                    'data_json'         => json_encode($r, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);
                $inserted++;
            }
        }

        // ✅ Align kas_login from related client (via kas_domains → kas_clients)
        DB::statement("
            UPDATE kas_dns_records AS r
            JOIN kas_domains AS d ON r.domain_id = d.id
            JOIN kas_clients AS c ON d.kas_client_id = c.id
            SET r.kas_login = c.account_login
            WHERE r.kas_login IS NULL OR r.kas_login <> c.account_login
        ");

        $linked = DB::table('kas_dns_records')
            ->whereNotNull('domain_id')
            ->count();

        $this->info("✅ Import finished.");
        $this->line("Total records inserted: {$inserted}");
        $this->line("Linked to domains: {$linked}");

        return 0;
    }
}
