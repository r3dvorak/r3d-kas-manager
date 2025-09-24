<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.2.0-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recipe;
use App\Services\RecipeRunner;

class RecipeRun extends Command
{
    protected $signature = 'recipe:run
    {recipeId : The recipe ID}
    {--domain= : The domain name (e.g. r3d.de)}
    {--account= : The account name (e.g. w01e77bc)}
    {--dry : Run in dry mode (simulation only)}';


    protected $description = 'Run a recipe with optional variables';

    public function handle(RecipeRunner $runner)
    {
        $recipe = Recipe::with('actions')->find($this->argument('recipeId'));

        if (!$recipe) {
            $this->error('Recipe not found.');
            return 1;
        }

        $vars = [];
        if ($this->option('domain')) {
            $vars['domain'] = $this->option('domain');
        }
        if ($this->option('account')) {
            $vars['account'] = $this->option('account');
        }

        $dryRun = $this->option('dry');

        $this->info("Running recipe: {$recipe->name} (dry-run: " . ($dryRun ? 'yes' : 'no') . ")");

        try {
            $run = $runner->run($recipe, $vars, $dryRun);

            $this->table(
                ['Action', 'Status', 'Details'],
                collect($run->result)->map(fn($r) => [
                    $r['action']['type'],
                    $r['status'],
                    $r['details'] ?? '',
                ])->toArray()
            );

            $this->info("Run status: {$run->status}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error running recipe: " . $e->getMessage());
            return 1;
        }
    }
}
