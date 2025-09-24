<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.1.0-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license     GNU General Public License version 2 or later
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRun;

class RecipeRunner
{
    public function run(Recipe $recipe, array $vars = [], bool $dryRun = false): RecipeRun
    {
        $run = new RecipeRun();
        $run->recipe_id = $recipe->id;
        $run->status = 'running';
        $run->variables = $vars;
        $run->save();

        $results = [];

        foreach ($recipe->actions as $action) {
            // Replace placeholders inside parameters
            $params = $this->substituteVariables($action->parameters, $vars);

            if ($dryRun) {
                $results[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'simulated',
                    'details' => $this->simulateAction($action->type, $params),
                ];
                continue;
            }

            try {
                $details = $this->executeAction($action->type, $params);
                $results[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'success',
                    'details' => $details,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'action'  => $action->toArray(),
                    'status'  => 'failed',
                    'details' => $e->getMessage(),
                ];
            }
        }

        $run->status = collect($results)->contains(fn($r) => $r['status'] === 'failed')
            ? 'failed'
            : 'success';
        $run->result = $results;
        $run->save();

        return $run;
    }

    /**
     * Replace {placeholders} in nested arrays/strings with actual values.
     */
    protected function substituteVariables($parameters, array $vars)
    {
        if (is_string($parameters)) {
            $parameters = json_decode($parameters, true) ?? $parameters;
        }

        $replace = [];
        foreach ($vars as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        array_walk_recursive($parameters, function (&$value) use ($replace) {
            if (is_string($value)) {
                $value = strtr($value, $replace);
            }
        });

        return $parameters;
    }

    protected function simulateAction(string $type, array $params): string
    {
        return match ($type) {
            'add_domain'     => "Would add domain " . ($params['domain'] ?? '{domain}') .
                                " to account " . ($params['account'] ?? '{account}'),

            'create_dns'     => "Would create DNS " . ($params['type'] ?? '?') .
                                " for " . ($params['domain'] ?? '{domain}') .
                                " -> " . ($params['value'] ?? '?'),

            'create_mailbox' => "Would create mailbox " .
                                ($params['mailbox'] ?? '{mailbox}') . '@' . ($params['domain'] ?? '{domain}'),

            'create_forward' => "Would forward " .
                                ($params['mailbox'] ?? '{mailbox}') . '@' . ($params['domain'] ?? '{domain}') .
                                " -> " . (isset($params['forwards'])
                                    ? implode(', ', (array)$params['forwards'])
                                    : '[no forwards defined]'
                                ),

            default          => "Would perform {$type}",
        };
    }


    protected function executeAction(string $type, array $params): string
    {
        // TODO: implement real KAS SOAP API calls here
        return "[Executed {$type}]";
    }
}
