<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.4.3-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recipe;
use App\Services\RecipeRunner;

class RecipeRun extends Command
{
    protected $signature = 'recipe:run {recipeId : The recipe ID} 
                            {--domain= : The domain name (e.g. r3d.de)} 
                            {--account= : The account name (e.g. w01e77bc)} 
                            {--dry : Run in dry mode (simulation only)} 
                            {--json : Output JSON instead of a table}';

    protected $description = 'Run a recipe with optional variables';

    public function handle(RecipeRunner $runner)
    {
        $recipe = Recipe::with('actions')->find($this->argument('recipeId'));

        if (!$recipe) {
            $this->error('Recipe not found.');
            return 1;
        }

        $vars = array_filter([
            'domain'  => $this->option('domain'),
            'account' => $this->option('account'),
        ]);

        $dryRun = $this->option('dry');
        $run = $runner->run($recipe, $vars, $dryRun);

        if ($this->option('json')) {
            $this->line(json_encode($run->toArray(), JSON_PRETTY_PRINT));
            return 0;
        }

        $rows = [];
        foreach ($run->result as $result) {
            $details = $result['details'];
            if (is_array($details)) {
                $details = json_encode($details, JSON_PRETTY_PRINT);
            }
            $rows[] = [
                $result['action']['type'],
                $result['status'],
                $details,
            ];
        }

        $this->info("Running recipe: {$recipe->name} (dry-run: " . ($dryRun ? 'yes' : 'no') . ")");
        $this->table(['Action', 'Status', 'Details'], $rows);
        $this->info("Run status: {$run->status}");

        return 0;
    }
}
