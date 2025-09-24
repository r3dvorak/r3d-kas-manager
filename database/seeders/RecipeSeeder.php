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
 * @license     GNU General Public License version 2 or later
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\RecipeAction;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Recipe 1: Standard Domain Setup
         */
        $recipe1 = Recipe::create([
            'name' => 'Standard Domain Setup',
            'description' => 'Adds domain, sets A and MX records',
            'variables' => [
                'server_ip' => '178.63.15.195',
                'mx_server' => 'mail.{domain}',
            ],
        ]);

        RecipeAction::create([
            'recipe_id' => $recipe1->id,
            'type' => 'add_domain',
            'parameters' => [
                'domain'  => '{domain}',
                'account' => '{account}',
            ],
            'order' => 1,
        ]);

        RecipeAction::create([
            'recipe_id' => $recipe1->id,
            'type' => 'create_dns',
            'parameters' => [
                'domain' => '{domain}',
                'type'   => 'A',
                'value'  => '{server_ip}',
            ],
            'order' => 2,
        ]);

        RecipeAction::create([
            'recipe_id' => $recipe1->id,
            'type' => 'create_dns',
            'parameters' => [
                'domain' => '{domain}',
                'type'   => 'MX',
                'value'  => '{mx_server}',
            ],
            'order' => 3,
        ]);


        /**
         * Recipe 2: Mailbox with Forwards
         */
        $recipe2 = Recipe::create([
            'name' => 'Mailbox with Forwards',
            'description' => 'Creates a mailbox and assigns multiple forwarding addresses',
            'variables' => [
                'mailbox'  => 'info',
                'password' => 'changeme123',
                'forwards' => [
                    'user1@example.com',
                    'user2@example.com',
                    'user3@example.com',
                ],
            ],
        ]);

        RecipeAction::create([
            'recipe_id' => $recipe2->id,
            'type' => 'create_mailbox',
            'parameters' => [
                'domain'   => '{domain}',
                'mailbox'  => '{mailbox}',
                'password' => '{password}',
            ],
            'order' => 1,
        ]);

        RecipeAction::create([
            'recipe_id' => $recipe2->id,
            'type' => 'create_forward',
            'parameters' => [
                'domain'  => '{domain}',
                'mailbox' => '{mailbox}',
                'forward' => '{forwards}', // 🔥 array placeholder
            ],
            'order' => 2,
        ]);
    }
}
