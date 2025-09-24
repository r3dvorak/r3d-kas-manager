<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.4.3-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRun;
use SoapClient;
use Exception;

class RecipeRunner
{
    protected SoapClient $client;
    protected string $kasUser;
    protected string $kasPassword;

    public function __construct()
    {
        $this->client = new SoapClient(
            "https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl",
            ['trace' => 1, 'exceptions' => true]
        );
        $this->kasUser = env('KAS_USER');
        $this->kasPassword = env('KAS_PASSWORD');
    }

    public function run(Recipe $recipe, array $variables = [], bool $dryRun = false): RecipeRun
    {
        $vars = array_merge($recipe->variables ?? [], $variables);

        $results = [];
        foreach ($recipe->actions as $action) {
            $params = $this->replacePlaceholders($action->parameters ?? [], $vars);

            if ($dryRun) {
                $results[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'simulated',
                    'details' => [
                        'message' => "Would call KAS API: {$action->type}",
                        'params'  => $params,
                    ],
                ];
                continue;
            }

            try {
                $response = $this->executeKasAction($action->type, $params);

                if (isset($response['error'])) {
                    $results[] = [
                        'action'  => $action->toArray(),
                        'status'  => 'error',
                        'details' => $response,
                    ];
                } else {
                    $results[] = [
                        'action'  => $action->toArray(),
                        'status'  => 'success',
                        'details' => $response,
                    ];
                }
            } catch (Exception $e) {
                $results[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'error',
                    'details' => ['exception' => $e->getMessage()],
                ];
            }
        }

        return RecipeRun::create([
            'recipe_id' => $recipe->id,
            'status'    => $dryRun ? 'simulated' : 'completed',
            'variables' => $vars,
            'result'    => $results,
        ]);
    }

    /**
     * Dispatch supported KAS API actions
     */
    protected function executeKasAction(string $type, array $params): array
    {
        return match ($type) {
            'get_domains'  => $this->kasRequest('get_domains', []),
            'get_accounts' => $this->kasRequest('get_accounts', []),

            'add_domain'   => $this->kasRequest('add_domain', [
                'domain_name'     => $params['domain'] ?? null,
                'domain_tld'      => $params['tld'] ?? 'de',
                'domain_path'     => '/web/',
                'php_version'     => '8.1',
                'redirect_status' => 0,
            ]),
            'create_dns'   => $this->kasRequest('add_dns_settings', [
                'record_name' => $params['domain'],
                'record_type' => $params['type'],
                'record_data' => $params['value'],
            ]),

            default        => ['error' => "Unknown action type: {$type}"],
        };
    }


    /**
     * Execute SOAP request to KAS API
     */
    protected function kasRequest(string $kasMethod, array $kasParams): mixed
    {
        $request = [
            'kas_login'     => $this->kasUser,
            'kas_auth_type' => 'plain',
            'kas_auth_data' => $this->kasPassword,
            'kas_action'    => $kasMethod,
        ] + $kasParams;

        // Direkt die SOAP-Methode aufrufen
        $response = $this->client->KasApi(json_encode($request));

        return $response; // ✅ Roh zurück, kein json_decode/encode mehr
    }

    protected function replacePlaceholders(array $params, array $vars): array
    {
        $out = [];
        foreach ($params as $k => $v) {
            if (is_string($v)) {
                $out[$k] = preg_replace_callback('/\{(\w+)\}/', fn($m) => $vars[$m[1]] ?? $m[0], $v);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
