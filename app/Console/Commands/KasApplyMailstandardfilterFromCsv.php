<?php

namespace App\Console\Commands;

use App\Services\Recipes\KasGateway;
use Illuminate\Console\Command;

class KasApplyMailstandardfilterFromCsv extends Command
{
    protected $signature = 'kas:apply-mailstandardfilter-from-csv
                            {--file= : Source CSV (default: storage/kas_responses/r3d-mailboxes-import-clean.csv)}
                            {--kas-login= : KAS account_login (default: w0213f06)}
                            {--filter= : Filter string, e.g. "greyl;rbl_cbl:mark" (default from app_settings: mail_standardfilter_default)}
                            {--only= : Only process specific target email(s) (comma-separated, applies after rename_to)}
                            {--sleep-ms=300 : Sleep between API calls}
                            {--execute : Actually apply filters (default: dry-run)}';

    protected $description = 'Apply KAS mail standard filters (spam protection) to mailboxes from CSV. Default is dry-run.';

    public function handle(KasGateway $kas): int
    {
        $file = $this->option('file') ?: storage_path('kas_responses/r3d-mailboxes-import-clean.csv');
        $login = $this->option('kas-login') ? strtolower(trim((string) $this->option('kas-login'))) : 'w0213f06';
        $sleepMs = (int) $this->option('sleep-ms');
        $execute = (bool) $this->option('execute');

        // NOTE: 'spamc_move' is listed by KAS but triggers action_syntax_incorrect when applied via API.
        $defaultFilter = 'rspamd;pdw;virus_mark;scbl:mark;brbl:mark';
        $filter = (string) ($this->option('filter') ?: \App\Models\AppSetting::getValue('mail_standardfilter_default', $defaultFilter));
        $filter = trim($filter);

        if ($filter === '') {
            $this->error("❌ Missing filter string. Provide --filter=... or set app_setting 'mail_standardfilter_default'.");
            return self::FAILURE;
        }

        if (!is_file($file)) {
            $this->error("❌ CSV not found: {$file}");
            return self::FAILURE;
        }

        $only = $this->option('only') ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('only'))))) : [];
        $onlySet = [];
        foreach ($only as $e) {
            $onlySet[strtolower($e)] = true;
        }

        $fh = fopen($file, 'r');
        if ($fh === false) {
            $this->error("❌ Cannot open CSV: {$file}");
            return self::FAILURE;
        }

        $header = fgetcsv($fh, 0, ';');
        if (!$header) {
            fclose($fh);
            $this->error('❌ CSV is empty.');
            return self::FAILURE;
        }

        $idx = [];
        foreach ($header as $i => $name) {
            $idx[strtolower(trim((string) $name))] = $i;
        }

        foreach (['mail_login'] as $col) {
            if (!array_key_exists($col, $idx)) {
                fclose($fh);
                $this->error("❌ Missing column '{$col}' in CSV header.");
                return self::FAILURE;
            }
        }

        $renameKey = null;
        foreach (['rename to', 'rename_to', 'renameto'] as $k) {
            if (array_key_exists($k, $idx)) {
                $renameKey = $k;
                break;
            }
        }

        // Build a lookup from email-address to internal KAS mail_login (e.g. m07cf8de).
        $accounts = $kas->fetchMailaccounts($login);
        $emailToInternal = [];
        foreach ($accounts as $a) {
            $internal = strtolower(trim((string) ($a['mail_login'] ?? '')));
            $addrs = strtolower(trim((string) ($a['mail_addresses'] ?? $a['mail_adresses'] ?? '')));
            if ($internal === '' || $addrs === '') continue;

            foreach (preg_split('/[\\s,;]+/', $addrs) ?: [] as $addr) {
                $addr = strtolower(trim((string) $addr));
                if ($addr === '' || !str_contains($addr, '@')) continue;
                $emailToInternal[$addr] = $internal;
            }
        }

        $rows = 0;
        $planned = 0;
        $ok = 0;
        $failed = 0;

        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            $rows++;
            $srcEmail = trim((string) ($row[$idx['mail_login']] ?? ''));
            if ($srcEmail === '') continue;

            $renameTo = $renameKey ? trim((string) ($row[$idx[$renameKey]] ?? '')) : '';
            $targetEmail = $renameTo !== '' ? $renameTo : $srcEmail;
            $targetEmail = strtolower($targetEmail);

            if (!str_contains($targetEmail, '@')) continue;
            if (!empty($onlySet) && !isset($onlySet[$targetEmail])) continue;

            $internal = $emailToInternal[$targetEmail] ?? '';
            if ($internal === '') {
                $this->error("SKIP unknown mailbox on KAS (not in get_mailaccounts): {$targetEmail}");
                $failed++;
                continue;
            }

            $planned++;

            if (!$execute) {
                $this->line("DRYRUN apply mailstandardfilter {$targetEmail} mail_login={$internal} (kas={$login})");
                continue;
            }

            $resp = $kas->callForLogin($login, 'add_mailstandardfilter', [
                'mail_login' => $internal,
                'filter' => $filter,
            ]);

            if (($resp['success'] ?? false) === true) {
                $this->info("OK apply mailstandardfilter {$targetEmail}");
                $ok++;
            } else {
                $msg = (string) ($resp['error'] ?? 'kas_failed');
                $this->error("FAIL apply mailstandardfilter {$targetEmail}: {$msg}");
                $failed++;
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        fclose($fh);

        $this->newLine();
        $this->info("OK: {$file}");
        $this->line("Mode: " . ($execute ? 'EXECUTE' : 'DRYRUN'));
        $this->line("Rows read: {$rows}");
        $this->line("Planned: {$planned}");
        $this->line("Applied: {$ok}");
        $this->line("Failed: {$failed}");
        if (!$execute) {
            $this->warn("Dry-run only. Re-run with --execute to apply filters.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
