<?php
/**
 * R3D KAS Manager
 *
 * Adapter: RecipeRunner -> delegiert an RecipeExecutor
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.24.1
 * @date      2025-10-09
 * @license   MIT License
 *
 * Legacy shim: erhalten der existierenden public API, intern delegiert an RecipeExecutor.
 */

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRun;
use Exception;

class RecipeRunner
{
    protected RecipeExecutor $executor;

    /**
     * @param RecipeExecutor|null $executor  - DI: falls nicht gesetzt, neu instanziieren
     */
    public function __construct(?RecipeExecutor $executor = null)
    {
        $this->executor = $executor ?? app(RecipeExecutor::class);
    }

    /**
     * Backwards-compatible run method.
     *
     * @param Recipe $recipe
     * @param array $variables
     * @param bool $dryRun
     * @return RecipeRun
     * @throws Exception
     */
    public function run(Recipe $recipe, array $variables = [], bool $dryRun = false): RecipeRun
    {
        // map legacy flags to executor options
        $options = [
            'dryrun' => $dryRun,
            'stop_on_error' => true,
        ];

        // If old code relied on single KAS credentials via env, allow variables to contain them:
        // e.g. $variables['kas_login'] and storage file are used by executor if present.
        if (!empty($variables['kas_login'])) {
            $options['kas_login'] = $variables['kas_login'];
        } elseif (env('KAS_USER')) {
            // Keep backward compatibility: if KAS_USER is set and no kas_login provided,
            // we inject it as kas_login (note: RecipeExecutor expects account_login keys from get_accounts.json).
            $variables['kas_login'] = env('KAS_USER');
        }

        // Execute via new executor
        $run = $this->executor->executeRecipe($recipe, $variables, auth()->user() ?? null, $options);

        // Old Runner returned a RecipeRun with 'result' as array; our executor sets result -> keep as-is.
        return $run;
    }
}
