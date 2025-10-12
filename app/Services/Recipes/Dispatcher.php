<?php
/**
 * R3D KAS Manager – Recipe Action Dispatcher
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.0-alpha
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
use App\Services\Recipes\Contracts\ActionHandler;

class Dispatcher
{
    /** @var ActionHandler[] */
    protected array $handlers;

    /**
     * @param iterable|ActionHandler[] $handlers
     */
    public function __construct(iterable $handlers)
    {
        // allow $handlers to be a generator/iterable from the container
        $this->handlers = is_array($handlers) ? $handlers : iterator_to_array($handlers);
    }

    /**
     * Dispatch a RecipeAction to the first handler that supports its type.
     *
     * @param RecipeAction $action
     * @param RecipeRun    $run
     * @param array        $vars
     * @param bool         $dryRun
     * @return array       Normalized result
     */
    public function dispatch(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($action->type)) {
                return $handler->handle($action, $run, $vars, $dryRun);
            }
        }

        return ['success' => false, 'error' => "Unknown action: {$action->type}"];
    }
}
