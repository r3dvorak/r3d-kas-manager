<?php
/**
 * R3D KAS Manager – Import Mailaccounts from List (using get_accounts.json)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.19.3-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * Reads:
 *   - storage/kas_responses/get_accounts.json  → credentials
 *   - storage/kas_responses/mail-logins.csv    → mail logins list
 * Fetches each mailbox via SOAP API (get_mailaccounts)
 * Writes merged JSON to storage/kas_responses/get_mailaccounts_all.json
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SoapClient;
use SoapFault;
use League\Csv\Reader;

class KasApiMailaccountsFromList extends Command
{
    protected $signature   = 'kas:import-mailaccounts-from-list {--limit=}';
    protected $description = '0.19.3-alpha Dry-run: import mail accounts from CSV list and fetch details via KAS API';
    protected string $apiWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
    protected int $delay = 2; // seconds between requests

    public function handle()
    {
        $csvPath = base_path('storage/kas_responses/mail-logins.csv');
        $accountsPath = base_path('storage/kas_responses/get_accounts.json');

        if (!file_exists($csvPath)) {
            $this->error("Missing {$csvPath}");
            return 1;
        }
        if (!file_exists($accountsPath)) {
            $this->error("Missing {$accountsPath}");
            return 1;
        }

        // --- Load account credentials from get_accounts.json
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

        // --- Load CSV
        $this->info("Reading CSV: {$csvPath}");
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);
        $records = iterator_to_array($csv->getRecords());
        $total = count($records);

        $limit = $this->option('limit');
        if ($limit && $limit > 0) {
            $records = array_slice($records, 0, (int)$limit);
            $this->warn("Limiting to first {$limit} records (of {$total}) …");
        }

        $this->info("Processing ".count($records)." mailbox entries …");
        $start = microtime(true);
        $allData = [];
        $index = 0;

        foreach ($records as $row) {
            $index++;
            $mailLogin = trim($row['user'] ?? '');
            $server    = trim($row['mailserver'] ?? '');
            $domain    = trim($row['domain'] ?? '');
            $emailAddr = trim($row['email'] ?? '');

            if (!$mailLogin || !$server) {
                $this->warn("Row {$index}: missing mail_login or mailserver");
                continue;
            }

            $accountLogin = substr($server, 0, 8);
            $plainPassword = $accounts[$accountLogin] ?? null;

            if (!$plainPassword) {
                $this->warn("Row {$index}: no credentials found for {$accountLogin}");
                continue;
            }

            $this->line("→ [{$index}] {$accountLogin} / {$mailLogin} ({$domain})");

            try {
                $soap = new SoapClient($this->apiWsdl);
                $params = [
                    'kas_login'        => $accountLogin,
                    'kas_auth_type'    => 'plain',
                    'kas_auth_data'    => $plainPassword,
                    'kas_action'       => 'get_mailaccounts',
                    'KasRequestParams' => ['mail_login' => $mailLogin]
                ];

                $rawResponse = $soap->KasApi(json_encode($params));
                $res = $this->normalizeResponse($rawResponse);

                $status = $res['Response']['ReturnString'] ?? $res['response']['status'] ?? null;
                $info   = $res['Response']['ReturnInfo']  ?? $res['response']['data'] ?? [];

                if ($status === 'TRUE' || $status === 'ok') {
                    $count = is_array($info) ? count($info) : 0;
                    $this->info("   ✓ {$count} result(s)");
                    $allData[$accountLogin][$mailLogin] = [
                        'domain'        => $domain,
                        'email'         => $emailAddr,
                        'mail_login'    => $mailLogin,
                        'data'          => $info,
                    ];
                } else {
                    $this->warn("   ✗ Empty or invalid response");
                    $allData[$accountLogin][$mailLogin] = [
                        'domain'        => $domain,
                        'email'         => $emailAddr,
                        'mail_login'    => $mailLogin,
                        'data'          => [],
                    ];
                }

            } catch (SoapFault $e) {
                $msg = $e->faultstring ?? (string)$e;
                if (stripos($msg, 'flood_protection') !== false) {
                    $this->warn("   ⚠ Flood protection (SOAP): waiting {$this->delay}s and retrying …");
                    sleep($this->delay + 2);
                    try {
                        $rawRetry = $soap->KasApi(json_encode($params));
                        $resRetry = $this->normalizeResponse($rawRetry);
                        $statusR  = $resRetry['response']['status'] ?? null;
                        $infoR    = $resRetry['response']['data'] ?? [];
                        if ($statusR === 'ok') {
                            $this->info("   ✓ 1 result (after retry)");
                            $allData[$accountLogin][$mailLogin] = [
                                'domain'     => $domain,
                                'email'      => $emailAddr,
                                'mail_login' => $mailLogin,
                                'data'       => $infoR,
                            ];
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

        // --- Write snapshot
        $outPath = base_path('storage/kas_responses/get_mailaccounts_all.json');
        file_put_contents($outPath, json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $time = round(microtime(true) - $start, 1);
        $this->info("✅ Results written to {$outPath} ({$time}s)");
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
