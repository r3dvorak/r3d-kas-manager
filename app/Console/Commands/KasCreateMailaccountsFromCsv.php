<?php

namespace App\Console\Commands;

use App\Services\Recipes\KasGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KasCreateMailaccountsFromCsv extends Command
{
    protected $signature = 'kas:create-mailaccounts-from-csv
                            {--file= : Source CSV (default: storage/kas_responses/r3d-mailboxes-import-clean.csv)}
                            {--kas-login= : Force a specific KAS account_login (e.g. w0213f06). If omitted, inferred per domain via kas_domains}
                            {--only= : Only process specific target email(s) (comma-separated, applies after rename_to)}
                            {--limit= : Only process first N rows}
                            {--sleep-ms=200 : Sleep between API calls to reduce flood protection}
                            {--execute : Actually create mailboxes (default: dry-run)}
                            {--skip-existing : Skip when mailbox already exists on KAS (recommended)}';

    protected $description = 'Create mailboxes on KAS from CSV (supports rename_to and quota MB). Default is dry-run.';

    public function handle(KasGateway $kas): int
    {
        $file = $this->option('file') ?: storage_path('kas_responses/r3d-mailboxes-import-clean.csv');
        $forceLogin = $this->option('kas-login') ? strtolower(trim((string) $this->option('kas-login'))) : null;
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $sleepMs = (int) $this->option('sleep-ms');
        $execute = (bool) $this->option('execute');
        $skipExisting = (bool) $this->option('skip-existing');
        $only = $this->option('only') ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('only'))))) : [];
        $onlySet = [];
        foreach ($only as $e) {
            $onlySet[strtolower($e)] = true;
        }

        if (!is_file($file)) {
            $this->error("❌ CSV not found: {$file}");
            return self::FAILURE;
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

        $need = ['mail_login', 'account_password', 'size'];
        foreach ($need as $col) {
            if (!array_key_exists($col, $idx)) {
                fclose($fh);
                $this->error("❌ Missing column '{$col}' in CSV header.");
                return self::FAILURE;
            }
        }

        // "rename to" column (with a space) is optional.
        $renameKey = null;
        foreach (['rename to', 'rename_to', 'renameto'] as $k) {
            if (array_key_exists($k, $idx)) {
                $renameKey = $k;
                break;
            }
        }

        $existingByLogin = [];
        $created = 0;
        $skipped = 0;
        $failed = 0;
        $rows = 0;

        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            $rows++;
            if ($limit !== null && ($created + $skipped + $failed) >= $limit) {
                break;
            }

            $srcEmail = trim((string) ($row[$idx['mail_login']] ?? ''));
            if ($srcEmail === '') {
                continue;
            }

            $password = (string) ($row[$idx['account_password']] ?? '');
            $sizeRaw = trim((string) ($row[$idx['size']] ?? ''));
            $renameTo = $renameKey ? trim((string) ($row[$idx[$renameKey]] ?? '')) : '';

            $targetEmail = $renameTo !== '' ? $renameTo : $srcEmail;
            $targetEmail = strtolower($targetEmail);

            if (!str_contains($targetEmail, '@')) {
                $this->warn("SKIP invalid email: {$targetEmail}");
                $skipped++;
                continue;
            }

            if (!empty($onlySet) && !isset($onlySet[$targetEmail])) {
                continue;
            }

            if ($password === '') {
                $this->warn("SKIP missing password for {$targetEmail}");
                $skipped++;
                continue;
            }

            [$passOk, $passWhy] = $this->validateKasPassword($password);
            if (!$passOk) {
                $this->error("SKIP password invalid for {$targetEmail}: {$passWhy}");
                $failed++;
                continue;
            }

            $quotaMb = $this->parseQuotaMb($sizeRaw);

            [$local, $domain] = explode('@', $targetEmail, 2);
            $kasLogin = $forceLogin ?: $this->inferKasLoginForDomain($domain);

            if ($kasLogin === null) {
                $this->error("❌ Cannot infer KAS login for domain '{$domain}'. Import domains first or pass --kas-login=");
                $failed++;
                continue;
            }

            if ($skipExisting) {
                if (!array_key_exists($kasLogin, $existingByLogin)) {
                    $existingByLogin[$kasLogin] = $this->fetchExistingMailLogins($kas, $kasLogin, $domain);
                }
                if (isset($existingByLogin[$kasLogin][$domain][$local])) {
                    $this->line("SKIP exists {$targetEmail} (kas={$kasLogin})");
                    $skipped++;
                    continue;
                }
            }

            if (!$execute) {
                $quotaHint = $quotaMb !== null ? " quota_mb={$quotaMb}" : '';
                $this->line("DRYRUN create {$targetEmail}{$quotaHint} (kas={$kasLogin})");
                $created++;
                continue;
            }

            $resp = $kas->callForLogin($kasLogin, 'add_mailaccount', [
                // KAS API expects local_part/domain_part for add_mailaccount.
                // Note: mailbox quota is not settable via the documented API.
                'local_part' => $local,
                'domain_part' => $domain,
                'mail_password' => $password,
                'webmail_autologin' => 'Y',
            ]);

            if (($resp['success'] ?? false) === true) {
                $quotaHint = $quotaMb !== null ? " (requested quota_mb={$quotaMb})" : '';
                $this->info("OK create {$targetEmail}{$quotaHint}");
                $created++;
                if ($skipExisting) {
                    $existingByLogin[$kasLogin][$domain][$local] = true;
                }
            } else {
                $msg = (string) ($resp['error'] ?? 'kas_failed');
                $this->error("FAIL {$targetEmail}: {$msg}");
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
        $this->line("Planned/Created: {$created}");
        $this->line("Skipped: {$skipped}");
        $this->line("Failed: {$failed}");
        if (!$execute) {
            $this->warn("Dry-run only. Re-run with --execute to create mailboxes.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function parseQuotaMb(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '-' || $raw === '—') {
            return null;
        }

        if (preg_match('/(\\d+(?:[\\.,]\\d+)?)\\s*GB/i', $raw, $m)) {
            $gb = (float) str_replace(',', '.', $m[1]);
            return (int) round($gb * 1024);
        }

        if (preg_match('/(\\d+)\\s*MB/i', $raw, $m)) {
            return (int) $m[1];
        }

        // Fallback: first integer
        if (preg_match('/(\\d+)/', $raw, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Basic pre-validation based on common KAS password requirements.
     * We do NOT print the password, only a rule hint.
     *
     * @return array{0:bool,1:string}
     */
    private function validateKasPassword(string $password): array
    {
        $p = (string) $password;
        if (strlen($p) < 12) return [false, 'min 12 characters'];
        if (!preg_match('/[0-9]/', $p)) return [false, 'needs a digit'];
        if (!preg_match('/[a-z]/', $p)) return [false, 'needs a lowercase letter'];
        if (!preg_match('/[A-Z]/', $p)) return [false, 'needs an uppercase letter'];
        if (!preg_match('/[^A-Za-z0-9]/', $p)) return [false, 'needs a special character'];
        if (preg_match('/[\\s]/', $p)) return [false, 'no spaces allowed'];
        // Disallowed characters seen in KAS UI hints.
        if (preg_match('/[&%$]/', $p)) return [false, 'contains disallowed character (&, %, $)'];
        return [true, 'ok'];
    }

    private function inferKasLoginForDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        if ($domain === '') return null;

        $row = DB::table('kas_domains')
            ->join('kas_clients', 'kas_domains.kas_client_id', '=', 'kas_clients.id')
            ->select('kas_clients.account_login')
            ->where('kas_domains.domain_full', $domain)
            ->whereNull('kas_domains.deleted_at')
            ->first();

        if (!$row || empty($row->account_login)) {
            return null;
        }

        return strtolower((string) $row->account_login);
    }

    /**
     * @return array<string, array<string, bool>> domain => local => true
     */
    private function fetchExistingMailLogins(KasGateway $kas, string $kasLogin, string $domain): array
    {
        $out = [];
        $out[$domain] = [];

        $accounts = $kas->fetchMailaccounts($kasLogin);
        foreach ($accounts as $acct) {
            $mlogin = strtolower((string) ($acct['mail_login'] ?? ''));
            if ($mlogin !== '') {
                $out[$domain][$mlogin] = true;
            }

            $addrs = strtolower((string) ($acct['mail_addresses'] ?? $acct['mail_adresses'] ?? ''));
            if ($addrs !== '' && str_contains($addrs, '@')) {
                foreach (preg_split('/[\\s,;]+/', $addrs) as $a) {
                    $a = trim($a);
                    if ($a === '' || !str_contains($a, '@')) continue;
                    [$l, $d] = explode('@', $a, 2);
                    if ($d === $domain && $l !== '') {
                        $out[$domain][$l] = true;
                    }
                }
            }
        }

        return $out;
    }
}
