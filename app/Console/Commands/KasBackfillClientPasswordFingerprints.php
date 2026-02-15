<?php

namespace App\Console\Commands;

use App\Models\KasClient;
use Illuminate\Console\Command;

class KasBackfillClientPasswordFingerprints extends Command
{
    protected $signature = 'kas:backfill-client-password-fingerprints
                            {--dryrun : Show changes without saving}
                            {--limit= : Optional limit for testing}
                            {--login= : Only backfill a specific account_login (e.g. w0213f06)}';

    protected $description = 'Backfills kas_clients.account_password_fingerprint based on the decrypted account_password value.';

    private function fingerprint(string $plain): string
    {
        $key = (string) config('app.key');
        return hash_hmac('sha256', $plain, $key);
    }

    public function handle(): int
    {
        $dry = (bool) $this->option('dryrun');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $onlyLogin = $this->option('login') ? strtolower(trim((string) $this->option('login'))) : null;

        $q = KasClient::query()->orderBy('account_login');
        if ($onlyLogin) {
            $q->where('account_login', $onlyLogin);
        }
        if ($limit && $limit > 0) {
            $q->limit($limit);
        }

        $clients = $q->get();
        $updated = 0;
        $skipped = 0;

        foreach ($clients as $c) {
            $plain = (string) ($c->account_password ?? '');
            if ($plain === '') {
                $skipped++;
                continue;
            }

            $fp = $this->fingerprint($plain);
            $currentFp = (string) ($c->account_password_fingerprint ?? '');
            if ($currentFp !== '' && hash_equals($currentFp, $fp)) {
                continue;
            }

            if ($dry) {
                $this->line("UPDATE {$c->account_login}  set account_password_fingerprint");
            } else {
                $c->update(['account_password_fingerprint' => $fp]);
            }
            $updated++;
        }

        $this->newLine();
        $this->info("Updated fingerprints: {$updated}");
        $this->line("Skipped (no password): {$skipped}");
        if ($dry) {
            $this->warn('Dry-run: no database changes were made.');
        }

        return self::SUCCESS;
    }
}

