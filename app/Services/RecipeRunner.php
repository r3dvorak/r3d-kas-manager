<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.4.2-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Service to execute automation recipes for all-inkl.com KAS API.
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
        $this->client = new SoapClient('https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl', [
            'trace' => 1,
            'exceptions' => true,
        ]);
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
                        'params'  => $params
                    ],
                ];
                continue;
            }

            try {
                $response = $this->executeKasAction($action->type, $params);

                if (isset($response['error']) && $response['error']) {
                    $results[] = [
                        'action'  => $action->toArray(),
                        'status'  => 'error',
                        'details' => ['error' => $response['message']],
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
                    'details' => ['error' => $e->getMessage()],
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

    protected function executeKasAction(string $type, array $params): array
    {
        return match ($type) {
            'add_domain' => $this->kasRequest('add_domain', [
                'domain_name' => $this->extractDomainName($params['domain']),
                'domain_tld' => $this->extractTld($params['domain']),
                'domain_path' => '/web/',
                'php_version' => '8.4',
                'redirect_status' => '0',
            ]),

            'create_dns' => $this->kasRequest('add_dns_settings', [
                'domain' => $params['domain'],
                'record_type' => $params['type'],
                'data' => $params['value'],
            ]),

            'create_mailbox' => $this->kasRequest('add_mailbox', [
                'domain' => $params['domain'],
                'mailbox' => $params['mailbox'],
                'password' => $params['password'] ?? 'changeme',
            ]),

            'create_forward' => $this->kasRequest('add_mail_forward', [
                'source' => $params['mailbox'] . '@' . $params['domain'],
                'target' => $params['target'],
            ]),

            default => [
                'error'   => true,
                'message' => "Unknown action type: {$type}",
            ],
        };
    }

    protected function kasRequest(string $kasMethod, array $kasParams): array
    {
        // ✅ KORREKTES Format basierend auf funktionierendem Beispiel
        $jsonRequest = json_encode([
            'kas_login'      => $this->kasUser,
            'kas_auth_type'  => 'plain',
            'kas_auth_data'  => $this->kasPassword,
            'kas_action'     => $kasMethod,
            'KasRequestParams' => $kasParams
        ]);

        try {
            $response = $this->client->KasApi($jsonRequest);

            // Response normalisieren
            if (is_object($response)) {
                $response = json_decode(json_encode($response), true);
            }

            return [
                'error' => false,
                'data'  => $response,
            ];

        } catch (Exception $e) {
            return [
                'error'   => true,
                'message' => $e->getMessage(),
                'request' => $jsonRequest, // Für Debugging
            ];
        }
    }

    protected function extractDomainName(string $domain): string
    {
        return substr($domain, 0, strrpos($domain, '.'));
    }

    protected function extractTld(string $domain): string
    {
        return substr(strrchr($domain, '.'), 1);
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