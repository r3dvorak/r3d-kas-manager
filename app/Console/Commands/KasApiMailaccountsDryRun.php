<?php
/**
 * R3D KAS Manager – KAS API Dry-Run (get_mailaccounts)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.18.9-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * Reads plain credentials from storage/kas_responses/account-passwords.csv (source of truth)
 * (All-inkl API format) and fetches mail accounts for every client.
 * Handles flood-protection throttling automatically.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Kas\AccountPasswordsCsv;
use SoapClient;
use SoapFault;

class KasApiMailaccountsDryRun extends Command
{
    protected $signature   = 'kas:dryrun-mailaccounts
                              {--limit= : Limit number of clients (for testing)}
                              {--clients= : Comma-separated list of KAS client logins (e.g. w0213f06)}
                              {--creds= : Path to account-passwords.csv (default: storage/kas_responses/account-passwords.csv)}';
    protected $description = '0.18.9-alpha Dry-run: fetch all mail accounts per client and store JSON (no DB writes)';

    protected string $apiWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
    protected int    $delay   = 5; // seconds between requests

    public function handle()
    {
        $limit = $this->option('limit');

        // --- Load credentials (source of truth)
        $credsPath = (string)($this->option('creds') ?: base_path('storage/kas_responses/account-passwords.csv'));
        if (!file_exists($credsPath)) {
            $this->error("Missing {$credsPath}");
            return 1;
        }
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

        ksort($accounts);
        if ($limit) {
            $accounts = array_slice($accounts, 0, (int) $limit, true);
        }

        $this->info('Fetching mail accounts for '.count($accounts).' clients …');
        $allData = [];

        foreach ($accounts as $login => $plainPassword) {
            $this->line("→ {$login}");

            try {
                $api = new SoapClient($this->apiWsdl);

                $payload = [
                    'kas_login'        => $login,
                    'kas_auth_type'    => 'plain',
                    'kas_auth_data'    => $plainPassword,
                    'kas_action'       => 'get_mailaccounts',
                    'KasRequestParams' => ['mail_login' => '*'],
                ];

                $rawResponse = $api->KasApi(json_encode($payload));
                $res = $this->normalizeResponse($rawResponse);

                // fallback with lowercase key if response not shaped
                if (!isset($res['response'])) {
                    $payloadLegacy = $payload;
                    unset($payloadLegacy['KasRequestParams']);
                    $payloadLegacy['kas_request'] = ['mail_login' => '*'];
                    $rawResponse = $api->KasApi(json_encode($payloadLegacy));
                    $res = $this->normalizeResponse($rawResponse);
                }

                $status  = $res['response']['status']  ?? null;
                $count   = (int)($res['response']['count'] ?? 0);
                $data    = $res['response']['data']    ?? [];
                $message = $res['response']['message'] ?? '';

                if ($status === 'ok') {
                    $this->info("   ✓ {$count} mail accounts");
                    $allData[$login] = ['count' => $count, 'mailaccounts' => $data];
                } else {
                    if (stripos($message, 'password') !== false) {
                        $this->error('   ✗ Password incorrect');
                    } elseif (stripos($message, 'payload') !== false) {
                        $this->error('   ✗ Payload invalid');
                    } elseif (stripos($message, 'flood_protection') !== false) {
                        $this->warn("   ⚠ Flood protection: waiting {$this->delay}s and retrying …");
                        sleep($this->delay + 2);
                        try {
                            $rawRetry = $api->KasApi(json_encode($payload));
                            $resRetry = $this->normalizeResponse($rawRetry);
                            $statusR  = $resRetry['response']['status'] ?? null;
                            $countR   = (int)($resRetry['response']['count'] ?? 0);
                            $dataR    = $resRetry['response']['data'] ?? [];
                            if ($statusR === 'ok') {
                                $this->info("   ✓ {$countR} mail accounts (after retry)");
                                $allData[$login] = ['count' => $countR, 'mailaccounts' => $dataR];
                                continue;
                            }
                        } catch (\Throwable $e2) {
                            $this->error("   ✗ Retry failed: {$e2->getMessage()}");
                        }
                        $allData[$login] = ['count' => 0, 'mailaccounts' => []];
                    } else {
                        $this->warn('   → API status: '.($message ?: $status ?: 'unknown'));
                        $allData[$login] = ['count' => 0, 'mailaccounts' => []];
                    }
                }
            }
            catch (SoapFault $e) {
                $msg = $e->faultstring ?? (string)$e;
                if (stripos($msg, 'kas_password_incorrect') !== false) {
                    $this->error("   ✗ Password mismatch for {$login}");
                } elseif (stripos($msg, 'flood_protection') !== false) {
                    $this->warn("   ⚠ Flood protection (SOAP): waiting {$this->delay}s and retrying …");
                    sleep($this->delay + 2);
                    try {
                        $rawRetry = $api->KasApi(json_encode($payload));
                        $resRetry = $this->normalizeResponse($rawRetry);
                        $statusR  = $resRetry['response']['status'] ?? null;
                        $countR   = (int)($resRetry['response']['count'] ?? 0);
                        $dataR    = $resRetry['response']['data'] ?? [];
                        if ($statusR === 'ok') {
                            $this->info("   ✓ {$countR} mail accounts (after retry)");
                            $allData[$login] = ['count' => $countR, 'mailaccounts' => $dataR];
                            continue;
                        }
                    } catch (\Throwable $e2) {
                        $this->error("   ✗ Retry failed: {$e2->getMessage()}");
                    }
                    $allData[$login] = ['count' => 0, 'mailaccounts' => []];
                } else {
                    $this->error("   ✗ SOAP: {$msg}");
                    $allData[$login] = ['count' => 0, 'mailaccounts' => []];
                }
            }
            catch (\Throwable $e) {
                $this->error("   ✗ Error: {$e->getMessage()}");
                $allData[$login] = ['count' => 0, 'mailaccounts' => []];
            }

            sleep($this->delay);
        }

        // --- Write snapshot
        $outPath = base_path('storage/kas_responses/get_mailaccounts_all.json');
        file_put_contents($outPath, json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("✅ Results written to {$outPath}");
        return 0;
    }

    /** Normalize SOAP responses that may be arrays or JSON strings */
    private function normalizeResponse($raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded)
                ? $decoded
                : ['response' => ['status' => 'error', 'message' => 'Unparsable JSON', 'raw' => $raw]];
        }
        return ['response' => ['status' => 'error', 'message' => 'Unknown SOAP return type']];
    }
}
