<?php
/**
 * R3D KAS Manager – KAS API Dry-Run (get_dns_settings)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.22.3-alpha
 * @date      2025-10-08
 * @license   MIT License
 *
 * Reads:
 *   - storage/kas_responses/get_accounts.json → credentials
 *   - storage/kas_responses/get_domains_all.json → domains per account
 *
 * Fetches DNS zone settings for each active domain (is_active = Y)
 * using the correct KAS account login and password.
 * Writes merged output to storage/kas_responses/get_dns_all.json.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SoapClient;
use Exception;

class KasImportDnsDryRun extends Command
{
    protected $signature = 'kas:import-dns-dryrun {--limit=0 : Limit number of domains for testing}';
    protected $description = 'Fetch DNS records (get_dns_settings) for all managed domains via KAS API (dry-run, merged).';
    protected string $apiWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
    protected int $delay = 3; // seconds between requests

    public function handle(): void
    {
        $accountsPath = storage_path('kas_responses/get_accounts.json');
        $domainsPath  = storage_path('kas_responses/get_domains_all.json');
        $outPath      = storage_path('kas_responses/get_dns_all.json');

        if (!File::exists($accountsPath) || !File::exists($domainsPath)) {
            $this->error('Missing one of the required files: get_accounts.json or get_domains_all.json');
            return;
        }

        // --- Load credentials
        $accounts = $this->loadAccounts($accountsPath);
        $this->info('Loaded '.count($accounts).' account credentials.');

        // --- Load domain data
        $domainData = json_decode(File::get($domainsPath), true);
        if (!is_array($domainData)) {
            $this->error('Invalid structure in get_domains_all.json');
            return;
        }

        $this->info('Loaded '.count($domainData).' account sections from get_domains_all.json');

        // --- Load previous DNS results
        $merged = File::exists($outPath)
            ? json_decode(File::get($outPath), true) ?? []
            : [];

        $soap = new SoapClient($this->apiWsdl);
        $limit = (int)$this->option('limit');
        $processed = 0;
        $updated = 0;

        foreach ($domainData as $kasLogin => $entry) {
            $password = $accounts[$kasLogin] ?? null;
            if (!$password) {
                $this->warn("Skipping account {$kasLogin}: missing credentials.");
                continue;
            }

            if (empty($entry['domains']) || !is_array($entry['domains'])) {
                $this->warn("No domains listed under {$kasLogin}.");
                continue;
            }

            foreach ($entry['domains'] as $domainInfo) {
                if (($domainInfo['is_active'] ?? 'Y') !== 'Y') continue;
                $domain = trim($domainInfo['domain_name'] ?? '');
                if (!$domain) continue;

                $processed++;
                if ($limit && $processed > $limit) break 2;

                $this->line("🔍 [{$kasLogin}] Fetching DNS for {$domain} …");

                $records = $this->fetchDnsRecords($soap, $kasLogin, $password, $domain, 'ns5.kasserver.com');

                if (empty($records)) {
                    $this->warn(" → No records from ns5, retrying with ns6 …");
                    $records = $this->fetchDnsRecords($soap, $kasLogin, $password, $domain, 'ns6.kasserver.com');
                }

                $merged[$domain] = $records;
                $count = is_array($records) ? count($records) : 0;
                $this->info(" → Retrieved {$count} DNS record(s).");
                $updated++;

                sleep($this->delay);
            }
        }

        File::put($outPath, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("✅ Dry-run completed. Updated {$updated} domains.");
        $this->line("Saved merged results to: {$outPath}");
        $this->line("Total domains in file: " . count($merged));
    }

    private function loadAccounts(string $path): array
    {
        $raw = json_decode(File::get($path), true);
        $entries = $raw['Response']['ReturnInfo'] ?? [];
        $accounts = [];

        foreach ($entries as $entry) {
            if (!empty($entry['account_login']) && !empty($entry['account_password'])) {
                $accounts[$entry['account_login']] = $entry['account_password'];
            }
        }
        return $accounts;
    }

    /**
     * Execute the KAS API call to get DNS settings with safe normalization.
     */
    private function fetchDnsRecords(SoapClient $soap, string $login, string $password, string $domain, string $ns): array
    {
        try {
            $params = [
                'kas_login'        => $login,
                'kas_auth_type'    => 'plain',
                'kas_auth_data'    => $password,
                'kas_action'       => 'get_dns_settings',
                'KasRequestParams' => [
                    'zone_host'  => "{$domain}.",
                    'nameserver' => $ns,
                ],
            ];

            $response = $soap->KasApi(json_encode($params));

            // ✅ FIX: Normalize response safely for all cases
            if (is_string($response)) {
                $decoded = json_decode($response, true);
            } elseif (is_object($response)) {
                $decoded = json_decode(json_encode($response), true);
            } elseif (is_array($response)) {
                $decoded = $response;
            } else {
                $decoded = [];
            }

            if (isset($decoded['Response']['ReturnInfo']) && is_array($decoded['Response']['ReturnInfo'])) {
                return $decoded['Response']['ReturnInfo'];
            }

            if (isset($decoded['Response']['ReturnString'])) {
                $msg = strtolower($decoded['Response']['ReturnString']);
                if (str_contains($msg, 'zone_not_found')) {
                    $this->warn("   ⚠ Zone not found for {$domain} ({$ns})");
                } elseif (str_contains($msg, 'flood')) {
                    $this->warn("   ⚠ Flood protection triggered for {$domain} ({$ns})");
                } elseif (str_contains($msg, 'denied')) {
                    $this->warn("   ⚠ Access denied for {$domain} ({$ns})");
                }
            }

            return [];
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $this->error("   ✗ {$domain} ({$ns}): {$msg}");
            return [];
        }
    }

}
