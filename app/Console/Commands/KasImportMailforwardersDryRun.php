<?php
/**
 * R3D KAS Manager – KAS API Dry-Run (get_mailforwards)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.20.5-alpha
 * @date      2025-10-08
 * @license   MIT License
 *
 * Reads:
 *   - storage/kas_responses/get_accounts.json → credentials
 * Queries each subaccount via SOAP API (get_mailforwards)
 * Merges results into storage/kas_responses/get_mailforwards_all.json
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SoapClient;
use SoapFault;

class KasImportMailforwardersDryRun extends Command
{
    protected $signature   = 'kas:import-mailforwarders-dryrun {--limit=}';
    protected $description = '0.20.5-alpha Dry-run: import mail forwarders from get_accounts.json via KAS API';
    protected string $apiWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
    protected int $delay = 2; // seconds between requests

    public function handle()
    {
        $accountsPath = base_path('storage/kas_responses/get_accounts.json');
        $outPath      = base_path('storage/kas_responses/get_mailforwards_all.json');

        if (!file_exists($accountsPath)) {
            $this->error("Missing {$accountsPath}");
            return 1;
        }

        // --- Load account credentials
        $rawAccounts = json_decode(file_get_contents($accountsPath), true);
        $entries = $rawAccounts['Response']['ReturnInfo'] ?? null;
        if (!$entries || !is_array($entries)) {
            $this->error('Invalid accounts JSON structure – expected Response.ReturnInfo[]');
            return 1;
        }

        $accounts = [];
        foreach ($entries as $entry) {
            if (!empty($entry['account_login']) && !empty($entry['account_password'])) {
                $accounts[$entry['account_login']] = $entry['account_password'];
            }
        }

        $this->info('Loaded '.count($accounts).' account credentials from get_accounts.json');

        // --- Merge with existing forwarders file
        $merged = [];
        if (file_exists($outPath)) {
            $merged = json_decode(file_get_contents($outPath), true) ?? [];
            $this->info('Merging with existing file ('.count($merged).' accounts).');
        }

        $limit = $this->option('limit');
        if ($limit && $limit > 0) {
            $accounts = array_slice($accounts, 0, (int)$limit, true);
            $this->warn("Limiting to first {$limit} accounts …");
        }

        $start = microtime(true);
        $updated = 0;

        foreach ($accounts as $kasLogin => $plainPassword) {
            $this->line("🔍 Querying get_mailforwards for {$kasLogin} …");

            try {
                $soap = new SoapClient($this->apiWsdl);
                $params = [
                    'kas_login'      => $kasLogin,
                    'kas_auth_type'  => 'plain',
                    'kas_auth_data'  => $plainPassword,
                    'kas_action'     => 'get_mailforwards'
                ];

                $rawResponse = $soap->KasApi(json_encode($params));
                $res = $this->normalizeResponse($rawResponse);

                $status = $res['Response']['ReturnString'] ?? null;
                $info   = $res['Response']['ReturnInfo'] ?? [];

                if ($status === 'TRUE' && is_array($info)) {
                    $count = count($info);
                    $this->info("   ✓ {$count} forwarder(s)");
                    $merged[$kasLogin] = $info;
                    $updated++;
                } else {
                    $this->warn("   ✗ Empty or invalid response");
                    $merged[$kasLogin] = [];
                }
            } catch (SoapFault $e) {
                $msg = $e->faultstring ?? (string)$e;
                if (stripos($msg, 'flood_protection') !== false) {
                    $this->warn("   ⚠ Flood protection: waiting {$this->delay}s …");
                    sleep($this->delay + 2);
                    continue;
                } else {
                    $this->error("   ✗ SOAP: {$msg}");
                }
            } catch (\Throwable $e) {
                $this->error("   ✗ Error: {$e->getMessage()}");
            }

            sleep($this->delay);
        }

        file_put_contents($outPath, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $time = round(microtime(true) - $start, 1);

        $this->info("✅ Dry-run finished: {$updated} accounts updated ({$time}s).");
        $this->info("Saved merged results to {$outPath}");
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
