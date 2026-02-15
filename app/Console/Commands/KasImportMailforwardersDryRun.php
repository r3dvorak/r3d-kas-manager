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
 *   - storage/kas_responses/account-passwords.csv → credentials (source of truth)
 * Queries each subaccount via SOAP API (get_mailforwards)
 * Merges results into storage/kas_responses/get_mailforwards_all.json
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Kas\AccountPasswordsCsv;
use SoapClient;
use SoapFault;

class KasImportMailforwardersDryRun extends Command
{
    protected $signature   = 'kas:import-mailforwarders-dryrun
                              {--limit= : Limit number of clients (for testing)}
                              {--clients= : Comma-separated list of KAS client logins (e.g. w0213f06)}
                              {--creds= : Path to account-passwords.csv (default: storage/kas_responses/account-passwords.csv)}';
    protected $description = 'Dry-run: import mail forwarders using credentials from account-passwords.csv via KAS API';
    protected string $apiWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
    protected int $delay = 2; // seconds between requests

    public function handle()
    {
        $credsPath = (string)($this->option('creds') ?: base_path('storage/kas_responses/account-passwords.csv'));
        $outPath      = base_path('storage/kas_responses/get_mailforwards_all.json');

        if (!file_exists($credsPath)) {
            $this->error("Missing {$credsPath}");
            return 1;
        }

        // --- Load account credentials (source of truth)
        $accounts = app(AccountPasswordsCsv::class)->load($credsPath);

        // Optional client filter
        $filter = collect(explode(',', (string)$this->option('clients')))
            ->map(fn($v) => strtolower(trim($v)))
            ->filter()
            ->toArray();
        if ($filter) {
            $accounts = array_intersect_key($accounts, array_flip($filter));
            $this->info('🎯 Limiting to clients: ' . implode(', ', array_keys($accounts)));
        }
        $this->info('Loaded '.count($accounts).' account credentials from account-passwords.csv');

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
