<?php
/**
 * R3D KAS Manager – Refresh API Passwords from get_accounts.json
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.17.2-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * Reads stored get_accounts.json and updates each KasClient.api_password.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KasClient;

class KasRefreshApiPasswords extends Command
{
    protected $signature = 'kas:refresh-api-passwords {--dryrun : Show changes without saving}';
    protected $description = 'Refreshes api_passwords for all KAS clients from storage/kas_responses/get_accounts.json.';

    public function handle(): int
    {
        $path = storage_path('kas_responses/get_accounts.json');

        if (!file_exists($path)) {
            $this->error("❌ File not found: {$path}");
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        $accounts = $data['Response']['ReturnInfo'] ?? [];

        if (empty($accounts)) {
            $this->error('❌ No accounts found in get_accounts.json');
            return Command::FAILURE;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($accounts as $acc) {
            $login = $acc['account_login'] ?? null;
            $plainPass = $acc['account_password'] ?? null;

            if (!$login || !$plainPass) {
                $this->warn("⚠️ Skipping incomplete entry: " . json_encode($acc));
                continue;
            }

            $client = KasClient::where('account_login', $login)->first();
            if (!$client) {
                $this->warn("⚠️ No local record for {$login}");
                continue;
            }

            if ($this->option('dryrun')) {
                $this->line("- would update {$login}");
                continue;
            }

            $client->update(['api_password' => $plainPass]);
            $updated++;
        }

        if ($this->option('dryrun')) {
            $this->info("💡 Dry-run complete. No DB changes made.");
        } else {
            $this->info("✅ Updated api_password for {$updated} clients.");
        }

        return Command::SUCCESS;
    }
}
