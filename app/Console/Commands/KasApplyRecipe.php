<?php
/**
 * R3D KAS Manager – Artisan command to apply a recipe.
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.24.1-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * app\Console\Commands\KasApplyRecipe.php
 * 
 * Usage:
 *  php artisan kas:apply-recipe {recipe_id} [--kas_login=] [--domain=] [--vars='{"foo":"bar"}'] [--dryrun]
 *
 * Examples:
 *  php artisan kas:apply-recipe 1 --kas_login=w01e77bc --domain=example.de
 *  php artisan kas:apply-recipe 2 --vars='{"domain_name":"example.de","kas_login":"w01e77bc"}' --dryrun
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RecipeExecutor;
use App\Models\Recipe;
use Illuminate\Support\Str;

class KasApplyRecipe extends Command
{
    protected $signature = 'kas:apply-recipe {recipe_id} {--kas_login=} {--domain=} {--vars=} {--dryrun}';
    protected $description = 'Execute a recipe by id (creates recipe_run, writes history).';

    protected RecipeExecutor $executor;

    public function __construct(RecipeExecutor $executor)
    {
        parent::__construct();
        $this->executor = $executor;
    }

    public function handle()
    {
        $rid = $this->argument('recipe_id');
        $recipe = Recipe::find($rid);
        if (!$recipe) {
            $this->error("Recipe {$rid} not found.");
            return 1;
        }

        $vars = [];
        if ($this->option('vars')) {
            $decoded = json_decode($this->option('vars'), true);
            if (!is_array($decoded)) {
                $this->error('Invalid --vars JSON');
                return 1;
            }
            $vars = $decoded;
        }

        if ($this->option('domain')) $vars['domain_name'] = $this->option('domain');
        if ($this->option('kas_login')) $vars['kas_login'] = $this->option('kas_login');

        $dry = $this->option('dryrun') ? true : false;

        $this->info("Starting recipe {$recipe->name} (id {$recipe->id})".($dry ? ' [DRYRUN]' : ''));

        $run = $this->executor->executeRecipe($recipe, $vars, auth()->user() ?? null, [
            'kas_login' => $vars['kas_login'] ?? null,
            'domain' => $vars['domain_name'] ?? null,
            'dryrun' => $dry,
        ]);

        $this->info("Run completed. status={$run->status} id={$run->id}");
        if ($run->result) {
            $this->info('Result: ' . json_encode($run->result));
        }

        return 0;
    }
}
