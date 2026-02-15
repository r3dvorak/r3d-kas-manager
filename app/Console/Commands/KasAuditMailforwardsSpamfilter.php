<?php

namespace App\Console\Commands;

use App\Services\Recipes\KasGateway;
use Illuminate\Console\Command;

class KasAuditMailforwardsSpamfilter extends Command
{
    protected $signature = 'kas:audit-mailforwards-spamfilter
                            {--kas-login= : KAS account_login (default: w0213f06)}
                            {--out= : Output CSV path (default: storage/kas_responses/mailforwards-spamfilter-audit.csv)}';

    protected $description = 'Fetch mail forwards and write a CSV showing spamfilter status per forward address.';

    public function handle(KasGateway $kas): int
    {
        $login = $this->option('kas-login') ? strtolower(trim((string) $this->option('kas-login'))) : 'w0213f06';
        $out = $this->option('out') ?: storage_path('kas_responses/mailforwards-spamfilter-audit.csv');

        $rows = $kas->fetchMailforwards($login);
        $total = count($rows);
        $enabled = 0;
        $disabled = 0;

        $fh = fopen($out, 'w');
        if ($fh === false) {
            $this->error("❌ Cannot write: {$out}");
            return self::FAILURE;
        }

        fputcsv($fh, ['mail_forward_address', 'mail_forward_targets', 'mail_forward_spamfilter'], ';');

        foreach ($rows as $fw) {
            $addr = (string) ($fw['mail_forward_address'] ?? $fw['mail_forward_adress'] ?? $fw['mail_forward'] ?? '');
            $targets = $fw['mail_forward_targets'] ?? '';
            if (is_array($targets)) {
                $targets = implode(',', $targets);
            }
            $spam = (string) ($fw['mail_forward_spamfilter'] ?? '');

            $sv = strtoupper(trim($spam));
            // KAS returns different markers (e.g. 'kaspdw') when enabled.
            // Treat any non-empty value except explicit off markers as enabled.
            $isOn = $sv !== '' && $sv !== 'N' && $sv !== '0';
            if ($isOn) $enabled++; else $disabled++;

            fputcsv($fh, [$addr, (string) $targets, $spam], ';');
        }

        fclose($fh);

        $this->info("✅ Wrote audit CSV: {$out}");
        $this->line("Total forwards: {$total}");
        $this->line("Spamfilter enabled: {$enabled}");
        $this->line("Spamfilter not enabled: {$disabled}");

        return self::SUCCESS;
    }
}
