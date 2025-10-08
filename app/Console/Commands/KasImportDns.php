<?php
/**
 * R3D KAS Manager – Import DNS Records from JSON
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.22.3-alpha
 * @date      2025-10-08
 * @license   MIT License
 *
 * Reads storage/kas_responses/get_dns_all.json
 * and imports all DNS records into kas_dns_records table.
 * Links each record to kas_domains if matching domain found.
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
    protected $description = '0.23.0-alpha - Import DNS records from get_dns_all.json into kas_dns_records table.';

    public function handle(): void
    {
        $jsonPath = storage_path('kas_responses/get_dns_all.json');

        if (!File::exists($jsonPath)) {
            $this->error("Missing file: {$jsonPath}");
            return;
        }

        if ($this->option('truncate')) {
            KasDnsRecord::truncate();
            $this->warn('Table kas_dns_records truncated.');
        }

        $data = json_decode(File::get($jsonPath), true);
        if (!is_array($data)) {
            $this->error('Invalid JSON structure.');
            return;
        }

        $domains = KasDomain::pluck('id', 'domain_full')->toArray();
        $countInserted = 0;
        $countLinked = 0;

        DB::beginTransaction();

        foreach ($data as $domainName => $records) {
            if (!is_array($records)) continue;

            $kasDomainId = $domains[$domainName] ?? null;

            foreach ($records as $rec) {
                KasDnsRecord::create([
                    'kas_login'          => $this->extractKasLogin($rec),
                    'domain_id'          => $kasDomainId,
                    'record_zone'        => $rec['record_zone'] ?? $domainName,
                    'record_name'        => $rec['record_name'] ?? '',
                    'record_type'        => $rec['record_type'] ?? '',
                    'record_data'        => $rec['record_data'] ?? '',
                    'record_aux'         => $rec['record_aux'] ?? 0,
                    'record_id_kas'      => $rec['record_id'] ?? null,
                    'record_changeable'  => $rec['record_changeable'] ?? 'Y',
                    'record_deleteable'  => $rec['record_deleteable'] ?? 'Y',
                    'data_json'          => json_encode($rec, JSON_UNESCAPED_SLASHES),
                ]);

                $countInserted++;
                if ($kasDomainId) $countLinked++;
            }
        }

        DB::commit();

        $this->info("✅ Import finished.");
        $this->line("Total records inserted: {$countInserted}");
        $this->line("Linked to domains: {$countLinked}");
        $this->line("Unlinked: " . ($countInserted - $countLinked));
    }

    private function extractKasLogin(array $rec): ?string
    {
        // Try to detect kas_login from MX or TXT DKIM record_data
        $data = $rec['record_data'] ?? '';
        if (preg_match('/(w\d{7,8})\.kasserver\.com/i', $data, $m)) {
            return $m[1];
        }
        return null;
    }
}
