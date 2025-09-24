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

class RecipeList extends Command
{
    protected $signature = 'recipe:list';
    protected $description = 'List all available recipes';

    public function handle()
    {
        $recipes = Recipe::all(['id', 'name', 'description', 'version']);

        if ($recipes->isEmpty()) {
            $this->warn('No recipes found.');
            return;
        }

        $this->table(
            ['ID', 'Name', 'Version', 'Description'],
            $recipes->toArray()
        );
    }
}
