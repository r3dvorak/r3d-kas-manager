<?php

namespace App\Services\Kas;

/**
 * Loads account credentials from storage/kas_responses/account-passwords.csv.
 *
 * This file is the primary source of truth in this project.
 */
class AccountPasswordsCsv
{
    /**
     * @return array<string,string> login => plainPassword
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException("CSV not found: {$path}");
        }

        $fh = fopen($path, 'r');
        if ($fh === false) {
            throw new \RuntimeException("Cannot open CSV: {$path}");
        }

        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);
            throw new \RuntimeException("CSV empty: {$path}");
        }

        $idx = [];
        foreach ($header as $i => $name) {
            $idx[strtolower(trim((string)$name))] = $i;
        }

        foreach (['account_login', 'account_password'] as $col) {
            if (!array_key_exists($col, $idx)) {
                fclose($fh);
                throw new \RuntimeException("CSV missing column '{$col}'");
            }
        }

        $out = [];
        while (($row = fgetcsv($fh)) !== false) {
            $login = strtolower(trim((string)($row[$idx['account_login']] ?? '')));
            $pw = (string)($row[$idx['account_password']] ?? '');
            if ($login === '' || trim($pw) === '') {
                continue;
            }
            $out[$login] = $pw;
        }
        fclose($fh);

        ksort($out);
        return $out;
    }
}

