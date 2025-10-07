<?php
/**
 * R3D KAS Manager – KAS API Dry-Run (get_subdomains)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.17.3-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * Fetches all subdomains for every KAS client and writes a merged JSON snapshot
 * to storage/kas_responses/get_subdomains_all.json for later import.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KasClient;
use SoapClient;
use Throwable;

class KasApiSubdomainsDryRun extends Command
{
    protected $signature   = 'kas:dryrun-subdomains {--limit= : Limit number of clients (for testing)}';
    protected $description = 'Performs a KAS API dry-run (get_subdomains) for all clients and saves the full JSON response.';

    public function handle(): int
    {
        $this->info('🔎 Connecting to KAS API — action: get_subdomains');

        $clients = KasClient::orderBy('account_login')->get();
        if ($limit = $this->option('limit')) {
            $clients = $clients->take((int)$limit);
        }

        $this->info("📂 Found {$clients->count()} client(s) to query.");

        $wsdl   = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
        $result = [];

        foreach ($clients as $index => $client) {
            $i = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $login = $client->account_login;
            $plainPass = $client->account_password; // decrypted by accessor

            if (empty($plainPass)) {
                $this->warn("[$i/{$clients->count()}] {$login} — missing API password, skipping");
                continue;
            }

            $this->line("[$i/{$clients->count()}] {$login} — querying get_subdomains...");

            try {
                $soap = new SoapClient($wsdl, [
                    'trace' => 1,
                    'exceptions' => true,
                    'connection_timeout' => 30,
                ]);

                $jsonRequest = json_encode([
                    'kas_login'        => $login,
                    'kas_auth_type'    => 'plain',
                    'kas_auth_data'    => $plainPass,
                    'kas_action'       => 'get_subdomains',
                    'KasRequestParams' => new \stdClass(),
                ], JSON_UNESCAPED_SLASHES);

                $response = $soap->KasApi($jsonRequest);
                $data = json_decode(json_encode($response), true);

                $domains = $data['Response']['ReturnInfo'] ?? [];

                $result[$login] = [
                    'count'      => count($domains),
                    'subdomains' => $domains,
                ];

                $this->info("   → got " . count($domains) . " subdomains.");

                sleep(2); // Flood delay (per All-Inkl docs)

            } catch (Throwable $e) {
                $this->warn("⚠️ {$login} failed: " . $e->getMessage());
                continue;
            }
        }

        // Save merged JSON
        $out = storage_path('kas_responses/get_subdomains_all.json');
        file_put_contents($out, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("✅ Dry-run complete — responses saved to:");
        $this->info("   {$out}");
        $this->info('💾 Clients queried: ' . count($result));

        return Command::SUCCESS;
    }
}
