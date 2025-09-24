<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.5.0-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Run a recipe using the correct KasClient credentials.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recipe;
use App\Models\User;
use App\Models\KasClient;
use App\Services\RecipeRunner;

class RecipeRun extends Command
{
    protected $signature = 'recipe:run 
        {recipeId : The recipe ID} 
        {--client-id= : Override KasClient ID (admin only)} 
        {--domain= : Domain name (e.g. r3d.de)} 
        {--account= : Account name (e.g. w01e77bc)} 
        {--dry : Run in dry mode (simulation only)} 
        {--json : Output JSON instead of a table}';

    protected $description = 'Run a recipe with the correct KAS client credentials';

    public function handle(RecipeRunner $runner)
    {
        $recipe = Recipe::with('actions')->find($this->argument('recipeId'));
        if (!$recipe) {
            $this->error("Recipe not found.");
            return 1;
        }

        // Determine user context (for now: we fake the "current user" as Admin)
        $user = User::where('email', 'admin@example.com')->first(); // TODO: replace with auth()->user() in web

        if (!$user) {
            $this->error("No user context found.");
            return 1;
        }

        // Find KasClient
        if ($user->isAdmin()) {
            $kasClient = null;
            if ($this->option('client-id')) {
                $kasClient = KasClient::find($this->option('client-id'));
            }
            if (!$kasClient) {
                $this->error("Admin must provide --client-id to run for a specific client.");
                return 1;
            }
        } else {
            $kasClient = $user->kasClient;
            if (!$kasClient) {
                $this->error("This user has no KasClient assigned.");
                return 1;
            }
        }

        $vars = array_filter([
            'domain'  => $this->option('domain'),
            'account' => $this->option('account'),
            'kas_login' => $kasClient->kas_login,
            'kas_auth_data' => $kasClient->kas_auth_data,
        ]);

        $dryRun = $this->option('dry');
        $this->info("Running recipe: {$recipe->name} for client {$kasClient->name} (dry-run: " . ($dryRun ? 'yes' : 'no') . ")");

        $run = $runner->run($recipe, $vars, $dryRun);

        if ($this->option('json')) {
            $this->line(json_encode($run->toArray(), JSON_PRETTY_PRINT));
        } else {
            $rows = [];
            foreach ($run->result as $result) {
                $details = $result['details'];
                if (is_array($details)) {
                    $details = json_encode($details, JSON_PRETTY_PRINT);
                }
                $rows[] = [
                    'Action'  => $result['action']['type'],
                    'Status'  => $result['status'],
                    'Details' => $details,
                ];
            }

            $this->table(['Action', 'Status', 'Details'], $rows);
            $this->line("Run status: {$run->status}");
        }

        return 0;
    }
}
