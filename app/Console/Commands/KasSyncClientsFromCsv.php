<?php

namespace App\Console\Commands;

use App\Models\KasClient;
use Illuminate\Console\Command;

class KasSyncClientsFromCsv extends Command
{
    protected $signature = 'kas:sync-clients-from-csv
                            {--source= : CSV source file (default: storage/kas_responses/account-passwords.csv)}
                            {--login= : Only sync a specific account_login (e.g. w0213f06)}
                            {--dryrun : Show changes without saving}
                            {--update-login-password : Also update the hashed Laravel login password for existing clients (default: off)}';

    protected $description = 'Sync kas_clients from storage/kas_responses/account-passwords.csv (creates missing, updates comment/api password).';

    private function fingerprint(string $plain): string
    {
        // Use app key as HMAC secret to avoid an offline-guessable hash.
        $key = (string) config('app.key');
        return hash_hmac('sha256', $plain, $key);
    }

    public function handle(): int
    {
        $source = $this->option('source') ?: storage_path('kas_responses/account-passwords.csv');
        $onlyLogin = $this->option('login') ? strtolower(trim((string)$this->option('login'))) : null;
        $dry = (bool)$this->option('dryrun');
        $updateLoginPassword = (bool)$this->option('update-login-password');

        if (!is_file($source)) {
            $this->error("❌ CSV not found: {$source}");
            return self::FAILURE;
        }

        $fh = fopen($source, 'r');
        if ($fh === false) {
            $this->error("❌ Cannot open CSV: {$source}");
            return self::FAILURE;
        }

        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);
            $this->error('❌ CSV is empty.');
            return self::FAILURE;
        }

        $idx = [];
        foreach ($header as $i => $name) {
            $idx[strtolower(trim((string)$name))] = $i;
        }

        foreach (['account_login', 'account_password', 'account_name'] as $col) {
            if (!array_key_exists($col, $idx)) {
                fclose($fh);
                $this->error("❌ Missing column '{$col}' in CSV header.");
                return self::FAILURE;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $rows = 0;

        while (($row = fgetcsv($fh)) !== false) {
            $rows++;
            $login = strtolower(trim((string)($row[$idx['account_login']] ?? '')));
            $plainPass = (string)($row[$idx['account_password']] ?? '');
            $name = trim((string)($row[$idx['account_name']] ?? ''));

            if ($login === '' || $plainPass === '') {
                $skipped++;
                continue;
            }

            if ($onlyLogin && $login !== $onlyLogin) {
                continue;
            }

            /** @var KasClient|null $client */
            $client = KasClient::where('account_login', $login)->first();

            if (!$client) {
                $fp = $this->fingerprint($plainPass);
                if ($dry) {
                    $this->line("CREATE {$login}  comment=\"{$name}\" (reason=missing_in_db)");
                } else {
                    KasClient::create([
                        'account_login' => $login,
                        'account_password' => $plainPass, // encrypted via mutator
                        'account_password_fingerprint' => $fp,
                        'password' => $plainPass,         // hashed via mutator
                        'account_comment' => $name,
                    ]);
                }
                $created++;
                continue;
            }

            $changes = [];
            $reasons = [];

            // Keep the stored comment aligned with CSV name (low risk).
            if ((string)($client->account_comment ?? '') !== $name) {
                $changes['account_comment'] = $name;
                $reasons[] = 'comment_diff';
            }

            // Update API password only when fingerprint differs.
            $fp = $this->fingerprint($plainPass);
            $currentFp = (string) ($client->account_password_fingerprint ?? '');
            if ($currentFp === '') {
                $changes['account_password'] = $plainPass;
                $changes['account_password_fingerprint'] = $fp;
                $reasons[] = 'fingerprint_missing';
            } elseif (!hash_equals($currentFp, $fp)) {
                $changes['account_password'] = $plainPass;
                $changes['account_password_fingerprint'] = $fp;
                $reasons[] = 'password_changed';
            }

            // Only touch the hashed Laravel login password when explicitly requested.
            if ($updateLoginPassword) {
                $changes['password'] = $plainPass;
                $reasons[] = 'update_login_password_enabled';
            }

            if (empty($changes)) {
                continue;
            }

            if ($dry) {
                $what = implode(', ', array_keys($changes));
                $why = $reasons ? (' reasons=['.implode(', ', array_unique($reasons)).']') : '';
                $commentOld = (string)($client->account_comment ?? '');
                $commentNew = (string)($changes['account_comment'] ?? '');
                $commentHint = '';
                if ($commentNew !== '' && $commentOld !== $commentNew) {
                    $commentHint = " comment:\"{$commentOld}\"→\"{$commentNew}\"";
                }

                $this->line("UPDATE {$login}  fields=[{$what}]{$why}{$commentHint}");
            } else {
                $client->update($changes);
            }

            $updated++;
        }

        fclose($fh);

        $this->newLine();
        $this->info("OK: {$source}");
        $this->line("Rows read: {$rows}");
        $this->line("Created:   {$created}");
        $this->line("Updated:   {$updated}");
        $this->line("Skipped:   {$skipped}");

        if ($dry) {
            $this->warn('Dry-run: no database changes were made.');
        }

        return self::SUCCESS;
    }
}
