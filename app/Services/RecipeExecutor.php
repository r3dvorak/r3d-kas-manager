<?php
/**
 * R3D KAS Manager – Recipe Executor (Dispatcher-based)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.5-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/RecipeExecutor.php
 *
 * Orchestrates recipe runs by delegating each RecipeAction to the
 * App\Services\Recipes\Dispatcher which in turn invokes the ActionHandler
 * implementations (AddDomain, UpdateDnsRecords, AddMailaccount, AddMailforward, ...).
 *
 * Backwards / convenience behaviour:
 *  - If a Dispatcher is not provided, the executor will attempt to resolve one
 *    from the container (app()->make(Dispatcher::class)). If that fails it will
 *    instantiate a default Dispatcher wired with the default handlers and a
 *    KasGateway instance so the class works when constructed manually in Tinker.
 * 
 * Orchestrates recipe runs by delegating each RecipeAction to the
 * App\Services\Recipes\Dispatcher which in turn invokes the ActionHandler
 * implementations (AddDomain, UpdateDnsRecords, AddMailaccount, AddMailforward, ...).
 *
 * Changes in 0.26.5:
 *  - Merge recipe default variables (recipes.variables) with runtime variables
 *    so kas_login/domain_name from recipe are honoured if not provided at runtime.
 *  - Persist merged variables in RecipeRun and pass them to action handlers.
 *  - Store merged request payload into recipe_action_history.request_payload.
 */

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRun;
use App\Models\RecipeAction;
use App\Models\RecipeActionHistory;
use App\Services\Recipes\Dispatcher;
use App\Services\Recipes\KasGateway;
use App\Services\Recipes\Actions\AddDomain;
use App\Services\Recipes\Actions\UpdateDnsRecords;
use App\Services\Recipes\Actions\AddMailaccount;
use App\Services\Recipes\Actions\AddMailforward;
use Illuminate\Support\Facades\Log;
use Throwable;
use Exception;

class RecipeExecutor
{
    protected Dispatcher $dispatcher;
    protected KasGateway $kas;

    /**
     * Constructor.
     *
     * @param Dispatcher|null $dispatcher (optional) Injected dispatcher
     * @param KasGateway|null $kas         (optional) injected gateway for manual fallback
     */
    public function __construct(?Dispatcher $dispatcher = null, ?KasGateway $kas = null)
    {
        $this->kas = $kas ?? new KasGateway();

        if ($dispatcher !== null) {
            $this->dispatcher = $dispatcher;
            return;
        }

        // Prefer container-resolved Dispatcher (if app() is available and bound)
        try {
            if (function_exists('app')) {
                $resolved = app(Dispatcher::class);
                if ($resolved instanceof Dispatcher) {
                    $this->dispatcher = $resolved;
                    return;
                }
            }
        } catch (Throwable $e) {
            // ignore and fall back to local construction
            Log::debug('RecipeExecutor: container resolution of Dispatcher failed: ' . $e->getMessage());
        }

        // Fallback: instantiate a Dispatcher with default handlers wired to a KasGateway
        $handlers = [
            new AddDomain($this->kas),
            new UpdateDnsRecords($this->kas),
            new AddMailaccount($this->kas),
            new AddMailforward($this->kas),
        ];
        $this->dispatcher = new Dispatcher($handlers);
    }

    /**
     * Convenience run method.
     *
     * @param Recipe $recipe
     * @param array $variables
     * @param bool $dryRun
     * @return RecipeRun
     */
    public function run(Recipe $recipe, array $variables = [], bool $dryRun = false): RecipeRun
    {
        return $this->executeRecipe($recipe, $variables, null, ['dryrun' => $dryRun]);
    }

    /**
     * Execute a recipe: iterate actions and dispatch them to the Dispatcher.
     *
     * Merges recipe default variables with runtime variables. Runtime variables
     * override recipe defaults.
     *
     * @param Recipe $recipe
     * @param array $variables
     * @param mixed $user (optional)
     * @param array $options (optional) ['dryrun' => bool]
     * @return RecipeRun
     */
    public function executeRecipe(Recipe $recipe, array $variables = [], $user = null, array $options = []): RecipeRun
    {
        // Decode recipe defaults (defensive)
        $recipeDefaults = [];
        if (!empty($recipe->variables)) {
            if (is_string($recipe->variables)) {
                $decoded = json_decode($recipe->variables, true);
                $recipeDefaults = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
            } elseif (is_array($recipe->variables)) {
                $recipeDefaults = $recipe->variables;
            }
        }

        // Merge defaults with provided variables (runtime vars win)
        $mergedVars = array_merge($recipeDefaults, $variables);

        $run = RecipeRun::create([
            'recipe_id'   => $recipe->id,
            'user_id'     => $user?->id ?? null,
            'status'      => 'running',
            'kas_login'   => $mergedVars['kas_login'] ?? null,
            'domain_name' => $mergedVars['domain_name'] ?? null,
            'variables'   => $mergedVars,
            'started_at'  => now(),
        ]);

        $dryRun = !empty($options['dryrun']);

        try {
            foreach ($recipe->actions as $action) {
                // Prepare vars for this action:
                // 1) start from merged recipe vars
                // 2) overlay action->parameters (action parameters override recipe defaults)
                $actionParams = is_array($action->parameters) ? $action->parameters : (is_string($action->parameters) ? json_decode($action->parameters, true) ?? [] : []);
                $varsForAction = array_merge($mergedVars, $actionParams);

                // Dispatch and normalize result
                try {
                    $result = $this->dispatcher->dispatch($action, $run, $varsForAction, $dryRun);
                    if (!is_array($result)) {
                        $result = ['success' => false, 'error' => 'Handler returned invalid response'];
                    }
                } catch (Throwable $hEx) {
                    $result = ['success' => false, 'error' => $hEx->getMessage()];
                }

                // Store history (store the merged request payload for clarity)
                $this->storeHistory($run, $action, $varsForAction, $result);
            }

            $run->status = 'finished';
            $run->result = ['finished' => true];
        } catch (Exception $e) {
            $run->status = 'error';
            $run->result = ['error' => $e->getMessage()];
            Log::error('RecipeExecutor.executeRecipe fatal: ' . $e->getMessage(), [
                'recipe_id' => $recipe->id,
            ]);
        }

        $run->finished_at = now();
        $run->save();

        return $run;
    }

    /**
     * Store action result in recipe_action_history table.
     *
     * @param RecipeRun $run
     * @param RecipeAction $action
     * @param array $requestPayload merged request payload passed to handler
     * @param array $result handler result
     * @return void
     */
    protected function storeHistory(RecipeRun $run, RecipeAction $action, array $requestPayload, array $result): void
    {
        $success = $result['success'] ?? false;
        $status  = $success ? 'success' : 'error';

        RecipeActionHistory::create([
            'recipe_id'         => $run->recipe_id,
            'recipe_run_id'     => $run->id,
            'recipe_action_id'  => $action->id,
            'kas_login'         => $run->kas_login,
            'domain_name'       => $run->domain_name,
            'affected_resource_type' => $result['affected_resource_type'] ?? null,
            'affected_resource_id'   => $result['affected_resource_id'] ?? null,
            'action_type'       => $action->type,
            'request_payload'   => $requestPayload,
            'response_payload'  => $result,
            'status'            => $status,
            'error_message'     => $result['error'] ?? null,
            'started_at'        => now(),
            'finished_at'       => now(),
            'created_at'        => now(),
        ]);
    }
}
