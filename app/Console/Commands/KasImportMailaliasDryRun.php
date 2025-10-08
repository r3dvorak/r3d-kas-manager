<?php
/**
 * R3D KAS Manager – Dry-Run Import: Mail Aliases (get_mailaliases)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.23.0-alpha
 * @date      2025-10-08
 * @license   MIT License
 *
 * Reads:
 *   - storage/kas_responses/get_accounts.json   → credentials (account_login + password)
 * Queries KAS API (get_mailaliases) for each account.
 * Writes merged JSON snapshot to storage/kas_responses/get_mailaliases_all.json
 * Handles flood protection automatically and merges with existing data if available.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SoapClient;
use SoapFault;

class KasImportMailaliasDryRun extends Command
{
    protected $signature = 'kas:import-mailalias-dryrun {--limit=}';
    protected $description = 'Dry-run: fetch all mail aliases from All-inkl KAS API (get_mailaliases) and save to JSON';
    protected string $apiWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
    protected int $delay = 2; // seconds between requests

    public function handle()
    {
        $accountsPath = base_path('storage/kas_responses/get_accounts.json');
        $outputPath   = base_path('storage/kas_responses/get_mailaliases_all.json');

        if (!file_exists($accountsPath)) {
            $this->error("Missing file: {$accountsPath}");
            return 1;
        }

        // --- Load credentials
        $rawAccounts = json_decode(file_get_contents($accountsPath), true);
        $entries = $rawAccounts['Response']['ReturnInfo'] ?? null;
        if (!$entries || !is_array($entries)) {
            $this->error('Invalid get_accounts.json format (expected Response.ReturnInfo[])');
            return 1;
        }

        $accounts = [];
        foreach ($entries as $entry) {
            if (!empty($entry['account_login']) && !empty($entry['account_password'])) {
                $accounts[$entry['account_login']] = $entry['account_password'];
            }
        }

        $this->info('Loaded ' . count($accounts) . ' account credentials from get_accounts.json');

        // --- Merge with existing file
        $merged = [];
        if (file_exists($outputPath)) {
            $existing = json_decode(file_get_contents($outputPath), true);
            if (is_array($existing)) {
                $merged = $existing;
                $this->info('Merging with existing file (' . count($merged) . ' accounts).');
            }
        }

        // --- Apply limit if set
        $limit = (int) ($this->option('limit') ?? 0);
        if ($limit > 0) {
            $accounts = array_slice($accounts, 0, $limit, true);
            $this->warn("Limiting to first {$limit} accounts …");
        }

        $soap = new SoapClient($this->apiWsdl);
        $updated = 0;

        foreach ($accounts as $login => $password) {
            $this->line("🔍 Querying get_mailaliases for {$login} …");

            $params = [
                'kas_login'        => $login,
                'kas_auth_type'    => 'plain',
                'kas_auth_data'    => $password,
                'kas_action'       => 'get_mailaliases',
                'KasRequestParams' => []
            ];

            try {
                $response = $soap->KasApi(json_encode($params));
                $decoded  = $this->normalizeResponse($response);

                $status = $decoded['Response']['ReturnString'] ?? null;
                $info   = $decoded['Response']['ReturnInfo'] ?? [];

                if ($status === 'TRUE' && is_array($info)) {
                    $count = count($info);
                    $this->info("   ✓ {$count} alias(es)");
                    $merged[$login] = $info;
                    $updated++;
                } else {
                    $this->warn("   ✗ No aliases or invalid response");
                    $merged[$login] = [];
                }

            } catch (SoapFault $e) {
                $msg = $e->faultstring ?? (string)$e;
                if (stripos($msg, 'flood_protection') !== false) {
                    $this->warn("   ⚠ Flood protection: waiting {$this->delay}s and retrying …");
                    sleep($this->delay + 2);
                    try {
                        $retry = $soap->KasApi(json_encode($params));
                        $decodedRetry = $this->normalizeResponse($retry);
                        $infoR = $decodedRetry['Response']['ReturnInfo'] ?? [];
                        $statusR = $decodedRetry['Response']['ReturnString'] ?? '';
                        if ($statusR === 'TRUE' && is_array($infoR)) {
                            $this->info("   ✓ after retry: " . count($infoR) . " alias(es)");
                            $merged[$login] = $infoR;
                            $updated++;
                            continue;
                        }
                    } catch (\Throwable $e2) {
                        $this->error("   ✗ Retry failed: {$e2->getMessage()}");
                    }
                } else {
                    $this->error("   ✗ SOAP: {$msg}");
                }
            } catch (\Throwable $e) {
                $this->error("   ✗ Error: {$e->getMessage()}");
            }

            sleep($this->delay);
        }

        file_put_contents($outputPath, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("✅ Dry-run completed. Updated {$updated} accounts.");
        $this->info("Saved merged results to: {$outputPath}");
        $this->line('Total accounts in file: ' . count($merged));

        return 0;
    }

    private function normalizeResponse($raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded)
                ? $decoded
                : ['Response' => ['ReturnString' => 'FALSE', 'ReturnInfo' => [], 'Raw' => $raw]];
        }
        return ['Response' => ['ReturnString' => 'FALSE', 'ReturnInfo' => []]];
    }
}
