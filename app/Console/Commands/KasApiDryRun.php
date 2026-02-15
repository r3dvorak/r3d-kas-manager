<?php

namespace App\Console\Commands;

use App\Services\Kas\KasSoapService;
use Illuminate\Console\Command;
use Throwable;

class KasApiDryRun extends Command
{
    protected $signature = 'kas:dryrun
                            {method=get_accounts}
                            {--otp= : Current 2FA code for session auth}
                            {--allow-secrets : Required for --save-raw / --show-secrets when method=get_accounts}
                            {--save-raw : Also write a *.raw.json file when method=get_accounts (contains secrets)}
                            {--show-secrets : Print full response including secrets (method=get_accounts)}';
    protected $description = 'Dry-run KAS SOAP API using environment credentials';

    public function handle(): int
    {
        $method = $this->argument('method');
        $otp = (string) $this->option('otp');

        $kasUser = (string) env('KAS_USER');
        $kasPass = (string) env('KAS_PASSWORD');
        $kasWsdl = (string) env('KAS_WSDL', 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl');

        if ($kasUser === '' || $kasPass === '') {
            $this->error('KAS_USER or KAS_PASSWORD is missing in .env');
            return Command::FAILURE;
        }

        $this->info("🔎 Connecting to KAS API — action: {$method}");

        try {
            $kas = app(KasSoapService::class);
            $client = $kas->createClient($kasWsdl);
            $result = $kas->callAction(
                $client,
                $kasUser,
                $kasPass,
                $method,
                [],
                ['otp' => $otp]
            );
            $arrayResponse = $result['data'];

            $this->info("✅ API call successful! auth_mode={$result['auth_mode']}");

            // For get_accounts, avoid leaking secrets by default.
            $printResponse = $arrayResponse;
            if ($method === 'get_accounts' && $this->option('show-secrets') && !$this->option('allow-secrets')) {
                $this->error('❌ Refusing to print secrets. Re-run with --allow-secrets.');
                return Command::FAILURE;
            }
            if ($method === 'get_accounts' && !$this->option('show-secrets')) {
                $printResponse = $this->sanitizeGetAccounts($arrayResponse);
            }

            $this->line(str_repeat('-', 60));
            $this->line(print_r($printResponse, true));
            $this->line(str_repeat('-', 60));

            // Save per method. For get_accounts: save sanitized by default.
            $logFile = storage_path("kas_responses/{$method}.json");
            @mkdir(dirname($logFile), 0755, true);
            if ($method === 'get_accounts') {
                $sanitized = $this->sanitizeGetAccounts($arrayResponse);
                file_put_contents($logFile, json_encode($sanitized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->info("📁 Saved sanitized: {$logFile}");

                if ($this->option('save-raw')) {
                    if (!$this->option('allow-secrets')) {
                        $this->error('❌ Refusing to write RAW secrets. Re-run with --allow-secrets.');
                        return Command::FAILURE;
                    }
                    $rawPath = storage_path("kas_responses/{$method}.raw.json");
                    file_put_contents($rawPath, json_encode($arrayResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    $this->warn("📁 Saved RAW (contains secrets): {$rawPath}");
                }
            } else {
                file_put_contents($logFile, json_encode($arrayResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->info("📁 Saved: {$logFile}");
            }
        } catch (Throwable $e) {
            $this->error('❌ Error during KAS API call: ' . $e->getMessage());
            if (isset($client)) {
                $this->line("\n🔍 Last SOAP Request:");
                $this->line($client->__getLastRequest());
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function sanitizeGetAccounts(array $resp): array
    {
        $copy = $resp;
        $ri = $copy['Response']['ReturnInfo'] ?? null;
        if (!is_array($ri)) {
            return $copy;
        }
        foreach ($ri as $i => $acc) {
            if (is_array($acc) && array_key_exists('account_password', $acc)) {
                unset($acc['account_password']);
                $ri[$i] = $acc;
            }
        }
        $copy['Response']['ReturnInfo'] = $ri;
        return $copy;
    }
}
