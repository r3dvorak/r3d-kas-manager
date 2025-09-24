<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.2.2-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\RecipeAction;

class R3dDeTestRecipeSeeder extends Seeder
{
    public function run(): void
    {
        $recipe = Recipe::create([
            'name' => 'Setup r3d.de Domain',
            'description' => 'Adds r3d.de domain to w01e77bc and sets A + MX records',
            'variables' => [
                'domain'    => 'r3d.de',
                'account'   => 'w01e77bc',
                'server_ip' => '178.63.15.195',
                'mx_server' => 'mail.r3d.de',
            ],
        ]);

        RecipeAction::create([
            'recipe_id'  => $recipe->id,
            'type'       => 'add_domain',
            'parameters' => ['domain' => '{domain}', 'account' => '{account}'],
            'order'      => 1,
        ]);

        RecipeAction::create([
            'recipe_id'  => $recipe->id,
            'type'       => 'create_dns',
            'parameters' => ['domain' => '{domain}', 'type' => 'A', 'value' => '{server_ip}'],
            'order'      => 2,
        ]);

        RecipeAction::create([
            'recipe_id'  => $recipe->id,
            'type'       => 'create_dns',
            'parameters' => ['domain' => '{domain}', 'type' => 'MX', 'value' => '{mx_server}'],
            'order'      => 3,
        ]);
    }
}
