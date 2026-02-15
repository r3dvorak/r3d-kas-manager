<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class KasAuditAccounts extends Command
{
    protected $signature = 'kas:audit-accounts
                            {--accounts= : Path to get_accounts.json (default: storage/kas_responses/get_accounts.json)}
                            {--truth= : Path to account-passwords.csv (default: storage/kas_responses/account-passwords.csv)}
                            {--outdir= : Output directory (default: storage/kas_responses)}
                            {--only= : Only audit a specific account_login (e.g. w0213f06)}';

    protected $description = 'Creates a sanitized get_accounts JSON (no passwords) and a diff report vs account-passwords.csv.';

    public function handle(): int
    {
        $accountsJson = $this->option('accounts') ?: storage_path('kas_responses/get_accounts.json');
        $truthCsv = $this->option('truth') ?: storage_path('kas_responses/account-passwords.csv');
        $outDir = $this->option('outdir') ?: storage_path('kas_responses');
        $only = $this->option('only') ? strtolower(trim((string)$this->option('only'))) : null;

        if (!is_file($accountsJson)) {
            $this->error("❌ File not found: {$accountsJson}");
            return self::FAILURE;
        }
        if (!is_file($truthCsv)) {
            $this->error("❌ File not found: {$truthCsv}");
            return self::FAILURE;
        }
        if (!is_dir($outDir) && !@mkdir($outDir, 0755, true) && !is_dir($outDir)) {
            $this->error("❌ Cannot create outdir: {$outDir}");
            return self::FAILURE;
        }

        $raw = json_decode((string)file_get_contents($accountsJson), true);
        if (!is_array($raw)) {
            $this->error("❌ Invalid JSON: {$accountsJson}");
            return self::FAILURE;
        }

        $returnInfo = $raw['Response']['ReturnInfo'] ?? [];
        if (!is_array($returnInfo)) {
            $this->error('❌ JSON missing Response.ReturnInfo array.');
            return self::FAILURE;
        }

        // --- Load truth CSV (account-passwords.csv)
        $fh = fopen($truthCsv, 'r');
        if ($fh === false) {
            $this->error("❌ Cannot open CSV: {$truthCsv}");
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

        $truth = [];
        while (($row = fgetcsv($fh)) !== false) {
            $login = strtolower(trim((string)($row[$idx['account_login']] ?? '')));
            if ($login === '') {
                continue;
            }
            if ($only && $login !== $only) {
                continue;
            }
            $truth[$login] = [
                'account_password_present' => trim((string)($row[$idx['account_password']] ?? '')) !== '',
                'account_name' => trim((string)($row[$idx['account_name']] ?? '')),
            ];
        }
        fclose($fh);

        // --- Build KAS map + sanitized JSON
        $kas = [];
        $sanitized = $raw;
        $sanitizedReturnInfo = [];

        foreach ($returnInfo as $acc) {
            if (!is_array($acc)) {
                continue;
            }
            $login = strtolower(trim((string)($acc['account_login'] ?? '')));
            if ($login === '') {
                continue;
            }
            if ($only && $login !== $only) {
                continue;
            }
            $kas[$login] = [
                'account_comment' => trim((string)($acc['account_comment'] ?? '')),
                'account_password_present' => array_key_exists('account_password', $acc) && trim((string)$acc['account_password']) !== '',
            ];

            // sanitize: remove secrets
            unset($acc['account_password']);
            $sanitizedReturnInfo[] = $acc;
        }

        $sanitized['Response']['ReturnInfo'] = $sanitizedReturnInfo;

        // --- Diff union
        $allLogins = array_values(array_unique(array_merge(array_keys($truth), array_keys($kas))));
        sort($allLogins);

        $missingInTruth = 0;
        $missingInKas = 0;
        $commentDiff = 0;

        $rows = [];
        foreach ($allLogins as $login) {
            $inTruth = array_key_exists($login, $truth);
            $inKas = array_key_exists($login, $kas);

            $action = 'ok';
            if ($inKas && !$inTruth) {
                $action = 'add_to_account-passwords.csv';
                $missingInTruth++;
            } elseif ($inTruth && !$inKas) {
                $action = 'not_in_get_accounts_json';
                $missingInKas++;
            } else {
                $kasComment = (string)($kas[$login]['account_comment'] ?? '');
                $truthName = (string)($truth[$login]['account_name'] ?? '');
                if ($kasComment !== '' && $truthName !== '' && $kasComment !== $truthName) {
                    $action = 'name_diff';
                    $commentDiff++;
                }
            }

            $rows[] = [
                'account_login' => $login,
                'in_get_accounts_json' => $inKas ? 'Y' : 'N',
                'in_account_passwords_csv' => $inTruth ? 'Y' : 'N',
                'kas_account_comment' => $inKas ? (string)$kas[$login]['account_comment'] : '',
                'csv_account_name' => $inTruth ? (string)$truth[$login]['account_name'] : '',
                'kas_password_present' => ($inKas && ($kas[$login]['account_password_present'] ?? false)) ? 'Y' : 'N',
                'csv_password_present' => ($inTruth && ($truth[$login]['account_password_present'] ?? false)) ? 'Y' : 'N',
                'action' => $action,
            ];
        }

        // --- Write outputs
        $sanitizedPath = rtrim($outDir, "\\/") . DIRECTORY_SEPARATOR . 'get_accounts.sanitized.json';
        file_put_contents($sanitizedPath, json_encode($sanitized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $reportPath = rtrim($outDir, "\\/") . DIRECTORY_SEPARATOR . 'accounts-audit-report.csv';
        $rfh = fopen($reportPath, 'w');
        if ($rfh === false) {
            $this->error("❌ Cannot write report: {$reportPath}");
            return self::FAILURE;
        }
        // German Excel friendly separator
        fputcsv($rfh, array_keys($rows[0] ?? [
            'account_login' => '',
            'in_get_accounts_json' => '',
            'in_account_passwords_csv' => '',
            'kas_account_comment' => '',
            'csv_account_name' => '',
            'kas_password_present' => '',
            'csv_password_present' => '',
            'action' => '',
        ]), ';');
        foreach ($rows as $r) {
            fputcsv($rfh, $r, ';');
        }
        fclose($rfh);

        $this->info("✅ Wrote sanitized JSON: {$sanitizedPath}");
        $this->info("✅ Wrote audit report:   {$reportPath}");
        $this->newLine();
        $this->line('Summary:');
        $this->line("  in get_accounts.json but missing in account-passwords.csv: {$missingInTruth}");
        $this->line("  in account-passwords.csv but missing in get_accounts.json: {$missingInKas}");
        $this->line("  name/comment differences: {$commentDiff}");

        return self::SUCCESS;
    }
}

