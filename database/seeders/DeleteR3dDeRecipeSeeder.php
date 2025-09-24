<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.4.4-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\RecipeAction;

class DeleteR3dDeRecipeSeeder extends Seeder
{
    public function run(): void
    {
        $recipe = Recipe::create([
            'name'        => 'Delete r3d.de Domain',
            'description' => 'Removes r3d.de domain from account w01e77bc',
            'variables'   => [
                'domain'  => 'r3d.de',
                'account' => 'w01e77bc',
            ],
        ]);

        RecipeAction::create([
            'recipe_id'  => $recipe->id,
            'type'       => 'delete_domain',
            'parameters' => ['domain' => '{domain}'],
            'order'      => 1,
        ]);
    }
}
