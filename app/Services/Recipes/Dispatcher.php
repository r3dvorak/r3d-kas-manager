<?php
/**
 * R3D KAS Manager – Recipe Action Dispatcher
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.7-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/Recipes/Dispatcher.php
 *
 * Purpose:
 *  Small dispatcher that maps a RecipeAction to the appropriate ActionHandler
 *  (Strategy pattern). Handlers are resolved/injected (e.g. via the container)
 *  and must implement App\Services\Recipes\Contracts\ActionHandler.
 *
 * Notes:
 *  - Handlers can be registered in a service provider (bind an array or iterable).
 *  - The dispatcher returns a normalized result array:
 *      ['success' => bool, 'Response' => mixed]  or  ['success'=>false,'error'=>string]
 */

namespace App\Services\Recipes;

use App\Models\RecipeAction;
use App\Models\RecipeRun;
use Illuminate\Contracts\Container\Container;

class Dispatcher
{
    protected Container $container;
    /** @var array<class-string|object> */
    protected array $handlers = [];

    /**
     * $handlers may be an array of class names (strings) or instantiated objects.
     * The RecipesServiceProvider below shows how we bind the dispatcher with handlers.
     */
    public function __construct(Container $container, array $handlers = [])
    {
        $this->container = $container;
        $this->handlers = $handlers;
    }

    /**
     * Dispatch the given action for a recipe run.
     *
     * @param RecipeAction $action
     * @param RecipeRun $run
     * @param array $vars merged variables for this action
     * @param bool $dryRun
     * @return array
     */
    public function dispatch(RecipeAction $action, RecipeRun $run, array $vars = [], bool $dryRun = false): array
    {
        $rawType = (string) $action->type;
        $normalizedType = $this->normalizeType($rawType);

        foreach ($this->handlers as $h) {
            // instantiate if it's a class name
            $handler = is_string($h) ? $this->container->make($h) : $h;

            // If handler offers supportedTypes(), use it (preferred)
            if (method_exists($handler, 'supportedTypes')) {
                try {
                    $types = (array) $handler->supportedTypes();
                } catch (\Throwable $e) {
                    $types = [];
                }

                foreach ($types as $t) {
                    if ($this->normalizeType((string) $t) === $normalizedType) {
                        return $this->invokeHandler($handler, $action, $run, $vars, $dryRun);
                    }
                }
            }

            // Fallback to supports(): try both raw and normalized inputs for compatibility.
            if (method_exists($handler, 'supports')) {
                try {
                    // try original raw type first (preserves existing handlers that expect the DB value)
                    if ($handler->supports($rawType)) {
                        return $this->invokeHandler($handler, $action, $run, $vars, $dryRun);
                    }

                    // also try the normalized variant (new behaviour)
                    if ($handler->supports($normalizedType)) {
                        return $this->invokeHandler($handler, $action, $run, $vars, $dryRun);
                    }
                } catch (\Throwable $e) {
                    // ignore and continue to next handler
                }
            }
        }

        return [
            'success' => false,
            'error' => 'Unknown action: ' . $rawType,
        ];
    }

    /**
     * Invoke the handler's handle() method, with a defensive check.
     */
    protected function invokeHandler($handler, RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun): array
    {
        if (method_exists($handler, 'handle')) {
            try {
                return $handler->handle($action, $run, $vars, $dryRun);
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'error' => 'Handler exception: ' . $e->getMessage(),
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Handler does not implement handle()',
        ];
    }

    /**
     * Normalization helper: lowercase + strip non-alphanum.
     */
    protected function normalizeType(string $type): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/', '', $type));
    }
}
