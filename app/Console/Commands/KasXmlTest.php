<?php
/**
 * R3D KAS Manager - Korrekte Implementation basierend auf all-inkl.com Beispiel
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.4.2-alpha
 * @date      2025-09-25
 * @license   MIT License
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;

class KasXmlTest extends Command
{
    protected $signature = 'kas:xmltest 
        {--command=add_domain : KAS command}
        {--params= : JSON parameters}';
    
    protected $description = 'Test all-inkl KAS API with correct format from working example';

    public function handle(): int
    {
        $command = $this->option('command');
        $paramsJson = $this->option('params') ?: '{}';
        $params = json_decode($paramsJson, true) ?? [];

        $this->info("🔎 Testing all-inkl KAS API - Command: {$command}");

        try {
            // KORREKTER Aufbau laut funktionierendem all-inkl Beispiel
            $client = new \SoapClient('https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl', [
                'trace' => 1,
                'exceptions' => 1,
            ]);

            // ✅ KORREKTES Format laut funktionierendem Beispiel:
            $jsonRequest = json_encode([
                'kas_login'      => env('KAS_USER'),      // ✅ korrekt
                'kas_auth_type'  => 'plain',              // ✅ korrekt  
                'kas_auth_data'  => env('KAS_PASSWORD'),  // ✅ korrekt
                'kas_action'     => $command,             // ✅ korrekt
                'KasRequestParams' => $params             // ✅ korrekt
            ]);

            $this->line("📤 JSON Request to SOAP:");
            $this->line($jsonRequest);

            // ✅ KORREKTER Aufruf laut Beispiel: KasApi(json_encode(...))
            $response = $client->KasApi($jsonRequest);

            $this->info("✅ API Call successful!");
            
            $this->line("\n📥 SOAP Request:");
            $this->line($client->__getLastRequest());
            
            $this->line("\n📤 SOAP Response:");
            $this->line($client->__getLastResponse());
            
            $this->line("\n💡 Parsed Response:");
            $this->line(print_r($response, true));

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            
            if (isset($client)) {
                $this->line("\n🔍 Last SOAP Request:");
                $this->line($client->__getLastRequest());
            }
            
            return self::FAILURE;
        }
    }
}