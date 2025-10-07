<?php
/**
 * R3D KAS Manager – Import All-Inkl Mailaccounts
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.18.0-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * Imports all KAS mailaccounts from local JSON (get_mailaccounts_all.json),
 * preserving chronological order (oldest clients first).
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KasClient;
use App\Models\KasMailaccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KasImportMailaccounts extends Command
{
    protected $signature = 'kas:import-mailaccounts {--fresh}';
    protected $description = 'Import mail accounts from JSON into database';

    public function handle()
    {
        $fresh = $this->option('fresh');
        $jsonPath = 'kas_responses/get_mailaccounts_all.json';

        if (!Storage::disk('local')->exists($jsonPath)) {
            $this->error("Missing file: storage/{$jsonPath}");
            return 1;
        }

        $data = json_decode(Storage::disk('local')->get($jsonPath), true);
        if (!$data) {
            $this->error("Invalid JSON data");
            return 1;
        }

        if ($fresh) {
            $this->warn("⚠ Truncating kas_mailaccounts table...");
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            KasMailaccount::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $inserted = 0;
        $updated = 0;

        foreach ($data as $login => $info) {
            $client = KasClient::where('account_login', $login)->first();
            if (!$client) {
                $this->warn("Skipping unknown client {$login}");
                continue;
            }

            $accounts = $info['mailaccounts'] ?? [];
            foreach ($accounts as $acc) {
                $record = KasMailaccount::updateOrCreate(
                    ['mail_login' => $acc['mail_login']],
                    [
                        'kas_client_id' => $client->id,
                        'mail_quota' => $acc['mail_quota'] ?? null,
                        'mail_alias' => $acc['mail_alias'] ?? null,
                        'mail_autoresponder' => ($acc['mail_autoresponder'] ?? 'no') === 'yes',
                        'mail_active' => ($acc['mail_active'] ?? 'yes') === 'yes',
                        'mail_target' => $acc['mail_target'] ?? null,
                    ]
                );

                $record->wasRecentlyCreated ? $inserted++ : $updated++;
            }
        }

        $this->info("✅ Import complete: {$inserted} inserted, {$updated} updated.");
        return 0;
    }
}
