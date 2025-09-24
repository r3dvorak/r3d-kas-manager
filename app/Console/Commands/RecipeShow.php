<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.1.0-alpha
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

class RecipeShow extends Command
{
    protected $signature = 'recipe:show {id}';
    protected $description = 'Show details of a recipe';

    public function handle()
    {
        $recipe = Recipe::with('actions')->find($this->argument('id'));

        if (!$recipe) {
            $this->error('Recipe not found.');
            return;
        }

        $this->info("Recipe: {$recipe->name} (v{$recipe->version})");
        $this->line("Description: {$recipe->description}");
        $this->line("Variables: " . json_encode($recipe->variables));

        $this->table(
            ['Order', 'Type', 'Parameters'],
            $recipe->actions->map(function ($a) {
                return [
                    $a->order,
                    $a->type,
                    json_encode($a->parameters),
                ];
            })->toArray()
        );
    }
}
