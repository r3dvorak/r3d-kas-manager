<?php
/**
 * R3D KAS Manager – Recipe Action Dispatcher
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.9-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/Recipes/Dispatcher.php
 * 
 * Responsibilities
 * - Normalize action types (case/underscores/dashes/spacing insensitive)
 * - Resolve and invoke the correct Action Handler
 * - Log every run into recipe_action_history (even on errors/unknown action)
 *
 * Handlers may expose either:
 *  - supportedTypes(): array  (preferred)
 *  - supports(string $type): bool (back-compat)
 *
 * A handler's handle() must return an array like:
 *  ['success' => bool, ...]
 */

namespace App\Services\Recipes;

use App\Models\RecipeAction;
use App\Models\RecipeRun;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\DB;

class Dispatcher
{
    /** @var \Illuminate\Contracts\Container\Container */
    protected $container;

    /**
     * @var array<class-string|object> List of handler class names or instances.
     */
    protected array $handlers = [];

    /**
     * Constructor is flexible:
     *  - new Dispatcher($container, $handlers)
     *  - new Dispatcher($handlers)            // handlers as first arg (array)
     *  - new Dispatcher()                     // uses app() and no handlers
     */
    public function __construct($containerOrHandlers = null, array $handlers = [])
    {
        if (is_array($containerOrHandlers)) {
            $this->container = app();
            $this->handlers  = $containerOrHandlers;
        } else {
            $this->container = $containerOrHandlers instanceof Container ? $containerOrHandlers : app();
            $this->handlers  = $handlers;
        }

        if (!$this->container instanceof Container) {
            $this->container = app();
        }
    }

    /**
     * Dispatch an action for a recipe run.
     */
    public function dispatch(RecipeAction $action, RecipeRun $run, array $vars = [], bool $dryRun = false): array
    {
        $rawType        = (string) ($action->type ?? '');
        $normalizedType = $this->normalizeType($rawType);

        foreach ($this->handlers as $h) {
            // Instantiate if class name was provided
            $handler = is_string($h) ? $this->container->make($h) : $h;

            // Prefer supportedTypes()
            if (method_exists($handler, 'supportedTypes')) {
                try {
                    $types = (array) $handler->supportedTypes();
                    foreach ($types as $t) {
                        if ($this->normalizeType((string) $t) === $normalizedType) {
                            $res = $this->invokeHandler($handler, $action, $run, $vars, $dryRun);
                            $this->logHistory($action, $run, $vars, $res, $dryRun);
                            return $res;
                        }
                    }
                } catch (\Throwable $e) {
                    // fall back to supports()
                }
            }

            // Back-compat supports()
            if (method_exists($handler, 'supports')) {
                try {
                    if ($handler->supports($rawType) || $handler->supports($normalizedType)) {
                        $res = $this->invokeHandler($handler, $action, $run, $vars, $dryRun);
                        $this->logHistory($action, $run, $vars, $res, $dryRun);
                        return $res;
                    }
                } catch (\Throwable $e) {
                    // try next handler
                }
            }
        }

        // Unknown action type — log as failed
        $res = ['success' => false, 'error' => 'Unknown action: ' . $rawType];
        $this->logHistory($action, $run, $vars, $res, $dryRun);
        return $res;
    }

    /**
     * Invoke the handler's handle() method and normalize return.
     */
    protected function invokeHandler($handler, RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun): array
    {
        if (!method_exists($handler, 'handle')) {
            return ['success' => false, 'error' => 'Handler does not implement handle()'];
        }

        try {
            $out = $handler->handle($action, $run, $vars, $dryRun);
            return is_array($out) ? $out : ['success' => false, 'error' => 'Invalid handler return'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Handler exception: ' . $e->getMessage()];
        }
    }

    /**
     * Lowercase + strip non-alphanumeric.
     */
    protected function normalizeType(string $type): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/', '', $type));
    }

    /**
     * Persist a history row for each attempted dispatch.
     * Swallows errors to never block execution.
     */
    protected function logHistory(RecipeAction $action, RecipeRun $run, array $vars, array $res, bool $dryRun): void
    {
        try {
            DB::table('recipe_action_history')->insert([
                // adjust column names if your schema differs:
                'recipe_run_id'    => $run->id ?? null,
                'recipe_action_id' => $action->id ?? null,
                'action_type'      => $action->type ?? null,
                'parameters_json'  => json_encode($vars, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'result_json'      => json_encode($res,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'success'          => !empty($res['success']) ? 1 : 0,
                'dry_run'          => $dryRun ? 1 : 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
