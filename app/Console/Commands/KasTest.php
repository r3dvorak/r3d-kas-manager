<?php
/**
 * R3D KAS Manager - Test Command für all-inkl.com XML-RPC API
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.3.7-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license     MIT License
 * 
 * Test command für all-inkl.com XML-RPC API.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;
use Illuminate\Support\Facades\Http;

class KasTest extends Command
{
    protected $signature = 'kas:test 
        {--domain=r3d.de : Domain name} 
        {--account=w01e77bc : Account name}';

    protected $description = 'Test all-inkl.com XML-RPC API connection';

    public function handle(): int
    {
        $domain  = $this->option('domain');
        $account = $this->option('account');

        $this->info("🔎 Testing all-inkl.com XML-RPC API for {$domain} / {$account}");

        // Korrekte XML-RPC Endpoints für all-inkl.com
        $xmlRpcEndpoints = [
            'https://kasapi.kasserver.com/xmlrpc/',
            'https://kasapi.kasserver.com/xmlrpc',
            'https://kasapi.kasserver.com/xmlrpc/index.php',
        ];

        foreach ($xmlRpcEndpoints as $endpoint) {
            $this->info("\n🧪 Testing XML-RPC Endpoint: {$endpoint}");
            
            try {
                // XML-RPC Request für all-inkl.com
                $xmlRequest = $this->buildXmlRpcRequest('add_domain', [
                    'account' => $account,
                    'domain'  => $domain,
                ]);

                $this->line("XML-RPC Request:");
                $this->line($xmlRequest);

                $response = Http::timeout(30)
                    ->withOptions(['verify' => false]) // SSL vorübergehend deaktivieren
                    ->withBody($xmlRequest, 'text/xml')
                    ->post($endpoint);

                $this->line("HTTP Status: " . $response->status());
                $this->line("Response: " . $response->body());

                if ($response->successful()) {
                    $this->info("✅ SUCCESS with XML-RPC Endpoint: {$endpoint}");
                    $this->parseXmlRpcResponse($response->body());
                    break;
                } else {
                    $this->error("❌ HTTP Error: " . $response->status());
                }

            } catch (Exception $e) {
                $this->error("❌ Failed with Endpoint {$endpoint}: " . $e->getMessage());
            }
        }

        // Teste auch mit PHP's XML-RPC Client
        $this->info("\n🧪 Testing with PHP XML-RPC Client");
        $this->testXmlRpcClient($domain, $account);

        return Command::SUCCESS;
    }

    protected function buildXmlRpcRequest(string $method, array $params): string
    {
        $paramsXml = '';
        foreach ($params as $key => $value) {
            $paramsXml .= "<param><value><string>{$value}</string></value></param>";
        }

        return '<?xml version="1.0"?>
<methodCall>
    <methodName>KasApi</methodName>
    <params>
        <param><value><struct>
            <member>
                <name>KasUser</name>
                <value><string>' . env('KAS_USER') . '</string></value>
            </member>
            <member>
                <name>KasAuthType</name>
                <value><string>plain</string></value>
            </member>
            <member>
                <name>KasPassword</name>
                <value><string>' . env('KAS_PASSWORD') . '</string></value>
            </member>
            <member>
                <name>KasRequest</name>
                <value><struct>
                    <member>
                        <name>KasCommand</name>
                        <value><string>' . $method . '</string></value>
                    </member>
                    <member>
                        <name>KasParams</name>
                        <value><struct>
                            ' . $this->buildParamsXml($params) . '
                        </struct></value>
                    </member>
                </struct></value>
            </member>
        </struct></value></param>
    </params>
</methodCall>';
    }

    protected function buildParamsXml(array $params): string
    {
        $xml = '';
        foreach ($params as $key => $value) {
            $xml .= "<member>
                <name>{$key}</name>
                <value><string>{$value}</string></value>
            </member>";
        }
        return $xml;
    }

    protected function testXmlRpcClient(string $domain, string $account): void
    {
        try {
            // Falls xmlrpc extension verfügbar ist
            if (function_exists('xmlrpc_encode_request')) {
                $request = [
                    'KasUser'     => env('KAS_USER'),
                    'KasAuthType' => 'plain', 
                    'KasPassword' => env('KAS_PASSWORD'),
                    'KasRequest'  => [
                        'KasCommand' => 'add_domain',
                        'KasParams'  => [
                            'account' => $account,
                            'domain'  => $domain,
                        ],
                    ],
                ];

                $xmlRequest = xmlrpc_encode_request('KasApi', $request);
                $this->line("PHP XML-RPC Request: " . $xmlRequest);

                $context = stream_context_create([
                    'http' => [
                        'method'  => 'POST',
                        'header'  => 'Content-Type: text/xml',
                        'content' => $xmlRequest
                    ]
                ]);

                $response = file_get_contents('https://kasapi.kasserver.com/xmlrpc/', false, $context);
                $this->line("PHP XML-RPC Response: " . $response);
            } else {
                $this->info("ℹ️  XML-RPC extension not available, using HTTP method");
            }

        } catch (Exception $e) {
            $this->error("❌ XML-RPC Client Error: " . $e->getMessage());
        }
    }

    protected function parseXmlRpcResponse(string $xmlResponse): void
    {
        try {
            $xml = simplexml_load_string($xmlResponse);
            if ($xml) {
                $this->info("✅ XML-RPC Response parsed successfully");
                
                // Versuche die Response zu parsen
                if (isset($xml->params->param->value->struct)) {
                    $this->processXmlRpcStruct($xml->params->param->value->struct);
                } else {
                    $this->line("Raw XML: " . $xmlResponse);
                }
            }
        } catch (Exception $e) {
            $this->error("❌ XML-RPC Parse Error: " . $e->getMessage());
        }
    }

    protected function processXmlRpcStruct($struct): void
    {
        foreach ($struct->member as $member) {
            $name = (string)$member->name;
            $value = $member->value;
            $this->line(" - {$name}: " . print_r($value, true));
        }
    }
}