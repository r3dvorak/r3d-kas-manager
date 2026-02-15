<?php
/**
 * R3D KAS Manager – Recipe Action Contract
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.0-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/Recipes/Contracts/ActionHandler.php
 *
 * Purpose:
 *  Common interface for all Recipe action handlers (Strategy pattern).
 *  Each handler declares if it supports a given action type and executes it,
 *  returning a normalized result shape understood by the executor.
 *
 * Expected result shape:
 *  - ['success' => bool, 'Response' => mixed]  // on success
 *  - ['success' => false, 'error' => string]   // on failure
 */

namespace App\Services\Recipes\Contracts;

use App\Models\RecipeAction;
use App\Models\RecipeRun;

interface ActionHandler
{
    /** Return true if this handler can handle $type (e.g. 'add_mailaccount'). */
    public function supports(string $type): bool;

    /**
     * Execute the action.
     * Return a normalized array like: ['success'=>bool, 'Response'=>mixed] (optionally include 'error').
     */
    public function handle(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array;
}
