<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.4.4-alpha
 * @date      2025-09-25
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license     MIT License
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
        $this->client = new SoapClient("https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl", [
            'trace' => 1,
            'exceptions' => true,
        ]);
        $this->kasUser     = env('KAS_USER');
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
                    'details' => json_encode([
                        'message' => "Would call KAS API: {$action->type}",
                        'params'  => $params,
                    ], JSON_PRETTY_PRINT),
                ];
                continue;
            }

            try {
                $response = $this->executeKasAction($action->type, $params);

                if (isset($response['error']) && $response['error']) {
                    $results[] = [
                        'action'  => $action->toArray(),
                        'status'  => 'error',
                        'details' => $response['message'],
                    ];
                } else {
                    $results[] = [
                        'action'  => $action->toArray(),
                        'status'  => 'success',
                        'details' => json_encode($response, JSON_PRETTY_PRINT),
                    ];
                }
            } catch (Exception $e) {
                $results[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'error',
                    'details' => $e->getMessage(),
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
     * Dispatch KAS API calls by action type
     */
    protected function executeKasAction(string $type, array $params): array
    {
        switch ($type) {
            case 'add_domain':
                return $this->kasRequest('add_domain', [
                    'domain_name' => $params['domain_name'] ?? explode('.', $params['domain'])[0] ?? '',
                    'domain_tld'  => $params['domain_tld']  ?? explode('.', $params['domain'])[1] ?? '',
                    'domain_path' => $params['domain_path'] ?? '/web/',
                    'php_version' => $params['php_version'] ?? '8.2',
                    'redirect_status' => $params['redirect_status'] ?? '0',
                ]);

            case 'delete_domain':
                // Falls nur {domain} angegeben wurde → split
                if (!empty($params['domain']) && empty($params['domain_name']) && empty($params['domain_tld'])) {
                    $parts = explode('.', $params['domain'], 2);
                    $params['domain_name'] = $parts[0] ?? $params['domain'];
                    $params['domain_tld']  = $parts[1] ?? '';
                }

                return $this->kasRequest('delete_domain', [
                    'domain_name' => $params['domain_name'] ?? '',
                    'domain_tld'  => $params['domain_tld'] ?? '',
                ]);

            case 'create_dns':
                return $this->kasRequest('add_dns_settings', [
                    'domain'      => $params['domain'],
                    'record_type' => $params['type'],
                    'data'        => $params['value'],
                ]);

            case 'create_mailbox':
                return $this->kasRequest('add_mailbox', [
                    'domain'   => $params['domain'],
                    'mailbox'  => $params['mailbox'],
                    'password' => $params['password'] ?? 'changeme',
                ]);

            case 'create_forward':
                return $this->kasRequest('add_mail_forward', [
                    'source' => $params['mailbox'] . '@' . $params['domain'],
                    'target' => $params['target'],
                ]);

            case 'get_domains':
                return $this->kasRequest('get_domains', []);

            case 'get_accounts':
                return $this->kasRequest('get_accounts', []);

            default:
                return ['error' => true, 'message' => "Unknown action type: {$type}"];
        }
    }

    /**
     * Execute SOAP request to KAS API
     */
    protected function kasRequest(string $kasMethod, array $kasParams): array
    {
        $request = [
            'kas_login'      => $this->kasUser,
            'kas_auth_type'  => 'plain',
            'kas_auth_data'  => $this->kasPassword,
            'kas_action'     => $kasMethod,
            'KasRequestParams' => $kasParams,
        ];

         $response = $this->client->KasApi(json_encode($request));

        return is_object($response)
            ? json_decode(json_encode($response), true)
            : (array)$response;
    }

    /**
     * Replace placeholders like {domain}
     */
    protected function replacePlaceholders(array $params, array $vars): array
    {
        $out = [];
        foreach ($params as $k => $v) {
            if (is_string($v)) {
                $out[$k] = preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($vars) {
                    return $vars[$matches[1]] ?? $matches[0];
                }, $v);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
