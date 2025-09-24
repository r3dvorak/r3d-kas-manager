
<?php

/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.2.1-alpha
 * @date      2025-09-24
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRun;
use App\Models\RecipeAction;

class RecipeRunner
{
    protected KasApiService $kas;

    public function __construct(KasApiService $kas)
    {
        $this->kas = $kas;
    }

    public function run(Recipe $recipe, array $vars = [], bool $dryRun = false): RecipeRun
    {
        $run = new RecipeRun();
        $run->recipe_id = $recipe->id;
        $run->status = 'success';
        $run->variables = $vars;
        $run->result = [];

        foreach ($recipe->actions as $action) {
            $parameters = $this->substituteVars($action->parameters, $vars);

            if ($dryRun) {
                $run->result[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'simulated',
                    'details' => "Would call KAS API: {$action->type}",
                ];
                continue;
            }

            // 🔌 Real API execution
            $response = $this->executeAction($action->type, $parameters);

            $run->result[] = [
                'action'  => $action->toArray(),
                'status'  => isset($response['error']) ? 'failed' : 'success',
                'details' => is_array($response) ? json_encode($response) : (string) $response,
            ];
        }

        $run->save();

        return $run;
    }

    /**
     * Replace placeholders {var} with runtime variables.
     */
    protected function substituteVars(array $parameters, array $vars): array
    {
        return collect($parameters)->map(function ($value) use ($vars) {
            if (!is_string($value)) {
                return $value;
            }

            return preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($vars) {
                return $vars[$matches[1]] ?? $matches[0];
            }, $value);
        })->toArray();
    }

    /**
     * Map action types to actual KAS API calls.
     */
    protected function executeAction(string $type, array $parameters): mixed
    {
        switch ($type) {
            case 'add_domain':
                return $this->kas->call('add_domain', [
                    'domain'  => $parameters['domain'],
                    'account' => $parameters['account'],
                ]);

            case 'create_dns':
                return $this->kas->call('add_dns_record', [
                    'domain' => $parameters['domain'],
                    'type'   => $parameters['type'],
                    'value'  => $parameters['value'],
                ]);

            case 'create_mailbox':
                return $this->kas->call('add_mailbox', [
                    'domain'   => $parameters['domain'],
                    'mailbox'  => $parameters['mailbox'],
                    'password' => $parameters['password'] ?? 'changeme123',
                ]);

            case 'create_forward':
                return $this->kas->call('add_mail_forward', [
                    'source' => $parameters['source'],
                    'target' => $parameters['target'],
                ]);

            default:
                return ['error' => true, 'message' => "Unknown action: $type"];
        }
    }
}
