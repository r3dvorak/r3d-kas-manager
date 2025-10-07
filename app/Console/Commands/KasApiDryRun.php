<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SoapClient;
use Throwable;

class KasApiDryRun extends Command
{
    protected $signature = 'kas:dryrun {method=get_accounts}';
    protected $description = 'Dry-run KAS SOAP API (direct hardcoded credentials for dev only)';

    public function handle(): int
    {
        $method = $this->argument('method');

        // 🔐 Hardcoded credentials for DEV TESTING ONLY
        $kasUser = 'w01954e3';
        $kasPass = 'Paad.Int-2023';
        $kasWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';

        $this->info("🔎 Connecting to KAS API — action: {$method}");

        try {
            $client = new SoapClient($kasWsdl, [
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 20,
            ]);

            // ✅ Use same format as working phptest.php
            $jsonRequest = json_encode([
                'kas_login'        => $kasUser,
                'kas_auth_type'    => 'plain',
                'kas_auth_data'    => $kasPass,
                'kas_action'       => $method,
                'KasRequestParams' => new \stdClass(),
            ], JSON_UNESCAPED_SLASHES);

            $this->line("📤 Request JSON:\n{$jsonRequest}");

            $response = $client->KasApi($jsonRequest);

            $this->info("✅ API call successful!");
            $arrayResponse = json_decode(json_encode($response), true);

            $this->line(str_repeat('-', 60));
            $this->line(print_r($arrayResponse, true));
            $this->line(str_repeat('-', 60));

            // Optional: save per method
            $logFile = storage_path("kas_responses/{$method}.json");
            @mkdir(dirname($logFile), 0755, true);
            file_put_contents($logFile, json_encode($arrayResponse, JSON_PRETTY_PRINT));
            $this->info("📁 Saved: {$logFile}");
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
}
