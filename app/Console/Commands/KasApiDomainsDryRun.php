<?php
/**
 * R3D KAS Manager – KAS API Dry-Run (get_domains)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.16.2-alpha
 * @date      2025-10-07
 * @license   MIT License
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KasClient;
use SoapClient;
use Throwable;

class KasApiDomainsDryRun extends Command
{
    protected $signature = 'kas:dryrun-domains 
                            {--clients= : Comma-separated list of KAS client logins (e.g. w01e667d,w01fcd89)} 
                            {--append : Append to existing get_domains_all.json instead of overwriting}';

    protected $description = 'Fetches domains via KAS API for all or selected clients and saves/updates get_domains_all.json.';

    public function handle(): int
    {
        $this->info('🔎 Starting KAS domain fetch (get_domains)...');

        $kasWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
        $outputFile = storage_path('kas_responses/get_domains_all.json');

        // Filter option
        $filter = collect(explode(',', $this->option('clients')))
            ->map(fn($v) => trim($v))
            ->filter()
            ->toArray();

        if ($filter) {
            $this->info('🎯 Limiting to clients: ' . implode(', ', $filter));
        }

        // Load existing JSON if append mode
        $existingData = [];
        if ($this->option('append') && file_exists($outputFile)) {
            $existingData = json_decode(file_get_contents($outputFile), true) ?? [];
        }

        // Determine which clients to query
        $clients = $filter
            ? KasClient::whereIn('account_login', $filter)->get()
            : KasClient::all();

        if ($clients->isEmpty()) {
            $this->error('❌ No matching kas_clients found.');
            return Command::FAILURE;
        }

        $total = $clients->count();
        $index = 1;

        foreach ($clients as $client) {
            $login = $client->account_login;
            $pass  = $client->account_password;

            $this->line("[{$index}/{$total}] {$login} — querying get_domains...");

            try {
                $soap = new SoapClient($kasWsdl, [
                    'trace' => true,
                    'exceptions' => true,
                    'connection_timeout' => 25,
                ]);

                $jsonRequest = json_encode([
                    'kas_login'        => $login,
                    'kas_auth_type'    => 'plain',
                    'kas_auth_data'    => $pass,
                    'kas_action'       => 'get_domains',
                    'KasRequestParams' => new \stdClass(),
                ], JSON_UNESCAPED_SLASHES);

                $response = $soap->KasApi($jsonRequest);
                $data = json_decode(json_encode($response), true);
                $domains = $data['Response']['ReturnInfo'] ?? [];

                if (empty($domains)) {
                    $this->warn("⚠️ {$login} returned no domains.");
                    $existingData[$login] = [
                        'count' => 0,
                        'domains' => [],
                    ];
                } else {
                    $existingData[$login] = [
                        'count' => count($domains),
                        'domains' => $domains,
                    ];
                    $this->info("✅ {$login}: " . count($domains) . " domains fetched.");
                }

                // Flood delay
                sleep(2);

            } catch (Throwable $e) {
                $this->warn("⚠️ {$login} failed: " . $e->getMessage());
                $existingData[$login] = [
                    'count' => 0,
                    'error' => $e->getMessage(),
                ];
            }

            $index++;
        }

        // Save updated JSON
        file_put_contents($outputFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->newLine();
        $this->info("💾 Results saved to: {$outputFile}");
        $this->info('✅ Completed KAS domain dry-run.');

        return Command::SUCCESS;
    }
}
