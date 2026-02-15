<?php

namespace App\Console\Commands;

use App\Services\Recipes\KasGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KasCreateMailforwardsFromCsv extends Command
{
    protected $signature = 'kas:create-mailforwards-from-csv
                            {--file= : Source CSV (default: storage/kas_responses/r3d-mailforwards-import-final.csv)}
                            {--kas-login= : Force a specific KAS account_login (e.g. w0213f06). If omitted, inferred per domain via kas_domains}
                            {--only= : Only process specific forward address(es) (comma-separated)}
                            {--limit= : Only process first N unique forward addresses}
                            {--sleep-ms=300 : Sleep between API calls to reduce flood protection}
                            {--execute : Actually create mail forwards (default: dry-run)}
                            {--skip-existing : Skip when forward already exists on KAS (recommended)}';

    protected $description = 'Create mail forwards on KAS from CSV. Default is dry-run.';

    public function handle(KasGateway $kas): int
    {
        $file = $this->option('file') ?: storage_path('kas_responses/r3d-mailforwards-import-final.csv');
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

        foreach (['mail_forward_address', 'mail_forward_targets'] as $col) {
            if (!array_key_exists($col, $idx)) {
                fclose($fh);
                $this->error("❌ Missing column '{$col}' in CSV header.");
                return self::FAILURE;
            }
        }

        // 1) Read + normalize + merge duplicates (same forward address).
        $map = []; // from => ['targets'=>set]
        $rows = 0;
        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            $rows++;
            $from = strtolower(trim((string) ($row[$idx['mail_forward_address']] ?? '')));
            $targetsRaw = trim((string) ($row[$idx['mail_forward_targets']] ?? ''));
            if ($from === '' || $targetsRaw === '') {
                continue;
            }
            if (!str_contains($from, '@')) {
                continue;
            }
            if (!empty($onlySet) && !isset($onlySet[$from])) {
                continue;
            }

            $targets = $this->splitTargets($targetsRaw);
            if (empty($targets)) {
                continue;
            }

            if (!isset($map[$from])) {
                $map[$from] = ['targets' => []];
            }
            foreach ($targets as $t) {
                $map[$from]['targets'][$t] = true;
            }
        }
        fclose($fh);

        $fromList = array_keys($map);
        sort($fromList);
        if ($limit !== null) {
            $fromList = array_slice($fromList, 0, $limit);
        }

        $existingByLogin = []; // kasLogin => set(from=>true)
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($fromList as $from) {
            $targets = array_keys($map[$from]['targets'] ?? []);
            sort($targets);

            [$local, $domain] = explode('@', $from, 2);
            $kasLogin = $forceLogin ?: $this->inferKasLoginForDomain($domain);
            if ($kasLogin === null) {
                $this->error("❌ Cannot infer KAS login for domain '{$domain}' (from {$from}). Import domains first or pass --kas-login=");
                $failed++;
                continue;
            }

            if ($skipExisting) {
                if (!array_key_exists($kasLogin, $existingByLogin)) {
                    $existingByLogin[$kasLogin] = $this->fetchExistingForwardAddresses($kas, $kasLogin);
                }
                if (isset($existingByLogin[$kasLogin][$from])) {
                    $this->line("SKIP exists {$from} (kas={$kasLogin})");
                    $skipped++;
                    continue;
                }
            }

            if (!$execute) {
                $this->line("DRYRUN create {$from} targets=" . count($targets) . " (kas={$kasLogin})");
                $created++;
                continue;
            }

            // Normalize targets:
            // - if target has no '@', treat as local-part on same domain.
            $targetsNorm = [];
            foreach ($targets as $t) {
                $t = strtolower(trim($t));
                if ($t === '') continue;
                if (!str_contains($t, '@')) {
                    $t = $t . '@' . $domain;
                }
                $targetsNorm[] = $t;
            }
            $targetsNorm = array_values(array_unique($targetsNorm));
            sort($targetsNorm);

            $params = [
                'local_part' => $local,
                'domain_part' => $domain,
            ];
            $n = 0;
            foreach ($targetsNorm as $t) {
                $n++;
                $params['target_' . $n] = $t;
            }

            $resp = $kas->callForLogin($kasLogin, 'add_mailforward', $params);

            if (($resp['success'] ?? false) === true) {
                $this->info("OK create {$from} targets=" . count($targetsNorm));
                $created++;
                if ($skipExisting) {
                    $existingByLogin[$kasLogin][$from] = true;
                }
            } else {
                $msg = (string) ($resp['error'] ?? 'kas_failed');
                // If the address is already a mailbox, a forward can't be created. Treat as skip.
                if (str_contains($msg, 'mail_forward_exists_as_emailaccount')) {
                    $this->warn("SKIP mailbox-exists {$from}");
                    $skipped++;
                } else {
                    $this->error("FAIL {$from}: {$msg}");
                    $failed++;
                }
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->newLine();
        $this->info("OK: {$file}");
        $this->line("Mode: " . ($execute ? 'EXECUTE' : 'DRYRUN'));
        $this->line("Rows read: {$rows}");
        $this->line("Unique forwards: " . count($fromList));
        $this->line("Planned/Created: {$created}");
        $this->line("Skipped: {$skipped}");
        $this->line("Failed: {$failed}");
        if (!$execute) {
            $this->warn("Dry-run only. Re-run with --execute to create forwards.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function splitTargets(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];

        // Targets may be "a@b.de,c@d.de" or newline-separated or similar.
        $parts = preg_split('/[\\s,;]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = strtolower(trim((string) $p));
            if ($p === '') continue;
            $out[] = $p;
        }
        return array_values(array_unique($out));
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
     * @return array<string,bool> forward_address => true
     */
    private function fetchExistingForwardAddresses(KasGateway $kas, string $kasLogin): array
    {
        $out = [];
        $forwards = $kas->fetchMailforwards($kasLogin);
        foreach ($forwards as $fw) {
            $addr = (string) ($fw['mail_forward_address'] ?? $fw['mail_forward_adress'] ?? $fw['mail_forward'] ?? '');
            $addr = strtolower(trim($addr));
            if ($addr !== '') {
                $out[$addr] = true;
            }
        }
        return $out;
    }
}
