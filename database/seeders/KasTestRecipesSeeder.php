<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.4.4-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * Seeder for test recipes: get_domains, get_accounts
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\RecipeAction;

class KasTestRecipesSeeder extends Seeder
{
    public function run(): void
    {
        // Recipe: get_domains
        $recipe1 = Recipe::create([
            'name'        => 'KAS Test: Get Domains',
            'description' => 'Calls KAS API get_domains to list all domains',
            'variables'   => [],
        ]);

        RecipeAction::create([
            'recipe_id'  => $recipe1->id,
            'type'       => 'get_domains',
            'parameters' => [],
            'order'      => 1,
        ]);

        // Recipe: get_accounts
        $recipe2 = Recipe::create([
            'name'        => 'KAS Test: Get Accounts',
            'description' => 'Calls KAS API get_accounts to list accounts',
            'variables'   => [],
        ]);

        RecipeAction::create([
            'recipe_id'  => $recipe2->id,
            'type'       => 'get_accounts',
            'parameters' => [],
            'order'      => 1,
        ]);
    }
}
