<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.4.1-alpha
 * @date      2025-09-25
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license     MIT License
 * 
 * Test command for All-Inkl KAS SOAP API.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SoapClient;

class KasSoapTest extends Command
{
    protected $signature = 'kas:soaptest 
        {--action=list_domains : KAS API action, e.g. list_domains, add_domain}
        {--domain= : Domain name without TLD (e.g. r3d)}
        {--tld= : Domain TLD (e.g. de)}';

    protected $description = 'Test SOAP call to All-Inkl KAS API (JSON request payload)';

    public function handle(): int
    {
        $url  = "https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl";
        $user = env("KAS_USER");
        $pass = env("KAS_PASSWORD");

        $action = $this->option('action');
        $domain = $this->option('domain');
        $tld    = $this->option('tld');

        $this->info("🔎 Calling KAS SOAP API: {$action}");

        try {
            $client = new SoapClient($url, ['trace' => 1, 'exceptions' => true]);

            // Default: keine params
            $params = [];

            // Wenn Domain-Aktion, dann Parameter hinzufügen
            if ($action === 'add_domain') {
                if (!$domain || !$tld) {
                    $this->error("❌ add_domain benötigt --domain und --tld");
                    return 1;
                }
                $params = [
                    'domain_name'    => $domain,
                    'domain_tld'     => $tld,
                    'domain_path'    => '/web/',
                    'php_version'    => '8.4',
                    'redirect_status'=> '0',
                ];
            }

            $request = [
                'kas_login'        => $user,
                'kas_auth_type'    => 'plain',
                'kas_auth_data'    => $pass,
                'kas_action'       => $action,
                'KasRequestParams' => $params,
            ];

            // 🚀 JSON-String als einziges Argument
            $response = $client->__soapCall("KasApi", [json_encode($request)]);

            $this->info("✅ SOAP call successful:");
            $this->line(print_r($response, true));

        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
