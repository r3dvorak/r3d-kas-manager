<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.2.3-alpha
 * @date      2025-09-24
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
    protected $client;

    public function __construct()
    {
        $this->client = new SoapClient(config('services.kas.wsdl'), [
            'login'    => config('services.kas.user'),
            'password' => config('services.kas.password'),
            'trace'    => true,
            'exceptions' => true,
        ]);
    }

    /**
     * Run a recipe (dry or real).
     */
    public function run(Recipe $recipe, array $variables = [], bool $dryRun = false): RecipeRun
    {
        $results = [];
        $status = 'success';

        try {
            foreach ($recipe->actions as $action) {
                $params = $this->replaceVars($action->parameters, $variables);

                if ($dryRun) {
                    $results[] = [
                        'action'  => $action->toArray(),
                        'status'  => 'simulated',
                        'details' => "Would call KAS API: {$action->type} " . json_encode($params),
                    ];
                    continue;
                }

                // Real API call (simplified stub for now)
                switch ($action->type) {
                    case 'add_domain':
                        $res = $this->client->kasApi([
                            'kas_login'    => config('services.kas.user'),
                            'kas_auth_type'=> 'plain',
                            'kas_auth_data'=> config('services.kas.password'),
                            'kas_action'   => 'add_domain',
                            'kas_param'    => $params,
                        ]);
                        break;

                    case 'create_dns':
                        $res = $this->client->kasApi([
                            'kas_login'    => config('services.kas.user'),
                            'kas_auth_type'=> 'plain',
                            'kas_auth_data'=> config('services.kas.password'),
                            'kas_action'   => 'create_dns_record',
                            'kas_param'    => $params,
                        ]);
                        break;

                    default:
                        $res = "Unsupported action: {$action->type}";
                        break;
                }

                $results[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'success',
                    'details' => is_string($res) ? $res : json_encode($res),
                ];
            }
        } catch (Exception $e) {
            $status = 'failed';
            $results[] = [
                'action'  => ['type' => 'exception'],
                'status'  => 'error',
                'details' => $e->getMessage(),
            ];
        }

        // Always save run (dry or real)
        $run = new RecipeRun();
        $run->recipe_id = $recipe->id;
        $run->status = $dryRun ? 'simulated' : $status;
        $run->variables = $variables;
        $run->result = $results;
        $run->save();

        return $run;
    }

    /**
     * Replace placeholders in parameters with actual variables.
     */
    protected function replaceVars($params, $vars)
    {
        return collect($params)->map(function ($value) use ($vars) {
            foreach ($vars as $key => $varValue) {
                $value = str_replace("{{$key}}", $varValue, $value);
            }
            return $value;
        })->toArray();
    }
}
