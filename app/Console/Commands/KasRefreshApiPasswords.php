<?php
/**
 * R3D KAS Manager – Refresh API Passwords from account-passwords.csv
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.17.2-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * Reads storage/kas_responses/account-passwords.csv (source of truth) and updates KasClient.account_password.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KasClient;
use App\Services\Kas\AccountPasswordsCsv;

class KasRefreshApiPasswords extends Command
{
    protected $signature = 'kas:refresh-api-passwords {--dryrun : Show changes without saving} {--creds= : Path to account-passwords.csv (default: storage/kas_responses/account-passwords.csv)}';
    protected $description = 'Refreshes KasClient.account_password for all KAS clients from account-passwords.csv.';

    public function handle(): int
    {
        $path = (string)($this->option('creds') ?: storage_path('kas_responses/account-passwords.csv'));

        if (!file_exists($path)) {
            $this->error("❌ File not found: {$path}");
            return Command::FAILURE;
        }

        $accounts = app(AccountPasswordsCsv::class)->load($path);

        $key = (string) config('app.key');

        $updated = 0;
        $skipped = 0;

        foreach ($accounts as $login => $plainPass) {
            $client = KasClient::where('account_login', $login)->first();
            if (!$client) {
                $this->warn("⚠️ No local record for {$login}");
                $skipped++;
                continue;
            }

            if ($this->option('dryrun')) {
                $this->line("- would update {$login}");
                continue;
            }

            $client->update([
                'account_password' => $plainPass,
                'account_password_fingerprint' => hash_hmac('sha256', $plainPass, $key),
            ]);
            $updated++;
        }

        if ($this->option('dryrun')) {
            $this->info("💡 Dry-run complete. No DB changes made.");
        } else {
            $this->info("✅ Updated account_password for {$updated} clients.");
        }

        return Command::SUCCESS;
    }
}
