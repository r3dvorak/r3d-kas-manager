<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.2.3-alpha
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

class RecipeRunner
{
    protected ?SoapClient $client = null;

    public function __construct()
    {
        if (extension_loaded('soap')) {
            $this->client = new SoapClient(env('KAS_WSDL'), [
                'login'    => env('KAS_USER'),
                'password' => env('KAS_PASSWORD'),
                'trace'    => 1,
                'exceptions' => true,
            ]);
        }
    }

    /**
     * Run a recipe.
     */
    public function run(Recipe $recipe, array $cliVars = [], bool $dryRun = false): RecipeRun
    {
        // Merge variables: recipe defaults first, CLI vars override
        $vars = array_merge($recipe->variables ?? [], $cliVars);

        $results = [];

        foreach ($recipe->actions()->orderBy('order')->get() as $action) {
            $params = $this->replacePlaceholders($action->parameters, $vars);

            if ($dryRun) {
                $results[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'simulated',
                    'details' => 'Would call KAS API: ' . $action->type . ' ' . json_encode($params),
                ];
                continue;
            }

            try {
                $response = $this->executeAction($action->type, $params);
                $results[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'success',
                    'details' => json_encode($response),
                ];
            } catch (\Exception $e) {
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
     * Replace placeholders in action parameters.
     */
    protected function replacePlaceholders($parameters, array $vars)
    {
        $json = json_encode($parameters);

        foreach ($vars as $key => $val) {
            $json = str_replace('{' . $key . '}', $val, $json);
        }

        return json_decode($json, true);
    }

    /**
     * Execute real KAS API action (to be extended).
     */
    protected function executeAction(string $type, array $params)
    {
        if (!$this->client) {
            throw new \Exception('SOAP client not initialized.');
        }

        switch ($type) {
            case 'add_domain':
                return $this->client->__soapCall('addDomain', [$params]);
            case 'create_dns':
                return $this->client->__soapCall('addDnsRecord', [$params]);
            case 'create_mailbox':
                return $this->client->__soapCall('addMailbox', [$params]);
            case 'create_forward':
                return $this->client->__soapCall('addForward', [$params]);
            default:
                throw new \Exception("Unknown action type: {$type}");
        }
    }
}
