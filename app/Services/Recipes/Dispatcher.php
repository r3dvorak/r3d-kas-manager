<?php
/**
 * R3D KAS Manager – Recipe Action Dispatcher
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.8-alpha
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
     * Constructor is flexible:
     * - new Dispatcher($container, $handlers)
     * - new Dispatcher($handlers)            // handlers as first arg (array)
     * - new Dispatcher()                     // uses app() and no handlers
     */
    public function __construct($containerOrHandlers = null, array $handlers = [])
    {
        // If first arg is an array, treat it as handlers list
        if (is_array($containerOrHandlers)) {
            $this->container = app();
            $this->handlers = $containerOrHandlers;
        } else {
            // otherwise first arg should be a Container or null
            $this->container = $containerOrHandlers instanceof Container
                ? $containerOrHandlers
                : app();

            $this->handlers = $handlers;
        }

        // defensive: ensure container is set
        if (!$this->container instanceof Container) {
            $this->container = app();
        }
    }

    public function dispatch(RecipeAction $action, RecipeRun $run, array $vars = [], bool $dryRun = false): array
    {
        $rawType = (string) $action->type;
        $normalizedType = $this->normalizeType($rawType);

        foreach ($this->handlers as $h) {
            $handler = is_string($h) ? $this->container->make($h) : $h;

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

            if (method_exists($handler, 'supports')) {
                try {
                    if ($handler->supports($rawType)) {
                        return $this->invokeHandler($handler, $action, $run, $vars, $dryRun);
                    }

                    if ($handler->supports($normalizedType)) {
                        return $this->invokeHandler($handler, $action, $run, $vars, $dryRun);
                    }
                } catch (\Throwable $e) {
                    // ignore and continue
                }
            }
        }

        return [
            'success' => false,
            'error' => 'Unknown action: ' . $rawType,
        ];
    }

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

    protected function normalizeType(string $type): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/', '', $type));
    }
}
