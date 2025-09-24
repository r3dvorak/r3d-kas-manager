<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.3.4-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Artisan command to run recipes and show results.
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
        {--mailbox= : Mailbox name (for email recipes)}
        {--password= : Mailbox password}
        {--target= : Forward target address}
        {--server-ip= : Server IP address}
        {--mx-server= : MX server hostname}
        {--dry : Run in dry mode (simulation only)}
        {--json : Output as JSON instead of table}';

    protected $description = 'Run a recipe with optional variables';

    public function handle(RecipeRunner $runner)
    {
        $recipe = Recipe::findOrFail($this->argument('recipeId'));

        $variables = array_filter([
            'domain'     => $this->option('domain'),
            'account'    => $this->option('account'),
            'mailbox'    => $this->option('mailbox'),
            'password'   => $this->option('password'),
            'target'     => $this->option('target'),
            'server_ip'  => $this->option('server-ip'),
            'mx_server'  => $this->option('mx-server'),
        ]);

        $dry = $this->option('dry');

        $this->info("Running recipe: {$recipe->name} (dry-run: " . ($dry ? 'yes' : 'no') . ")");

        $run = $runner->run($recipe, $variables, $dry);

        if ($this->option('json')) {
            $this->line(json_encode([
                'recipe'   => $recipe->only(['id', 'name', 'description']),
                'status'   => $run->status,
                'result'   => $run->result,
                'vars'     => $run->variables,
                'datetime' => $run->created_at,
            ], JSON_PRETTY_PRINT));
            return;
        }

        // --- FIX: ensure details is always a string ---
        $rows = [];
        foreach ($run->result as $result) {
            $details = $result['details'];
            if (is_array($details)) {
                $details = json_encode($details, JSON_PRETTY_PRINT);
            }

            $rows[] = [
                'action'  => $result['action']['type'] ?? 'N/A',
                'status'  => $result['status'] ?? 'N/A',
                'details' => $details ?? '',
            ];
        }

        $this->table(['Action', 'Status', 'Details'], $rows);
        $this->line("Run status: {$run->status}");
    }
}
