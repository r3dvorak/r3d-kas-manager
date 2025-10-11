<?php
/**
 * R3D KAS Manager — Seeder: Create Mailbox + Forwarder
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.25.0-alpha
 * @date      2025-10-10
 * @license   MIT License
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\RecipeAction;

class RecipeMailboxSeeder extends Seeder
{
    public function run(): void
    {
        $name = 'Create Mailbox + Forwarder';
        $recipe = Recipe::where('name', $name)->first();

        if ($recipe) {
            $recipe->actions()->delete();
            $recipe->delete();
        }

        $recipe = Recipe::create([
            'name'        => $name,
            'description' => 'Create a default mailbox and one forwarder (after domain creation).',
            'category'    => 'mail',
            'is_template' => false,
            'version'     => 1,
            'variables'   => [
                'default_password' => 'ChangeMe123!',
                'mail_quota_mb'    => 1024,
                'mail_account'     => 'info',
                'mail_forward_from'=> 'kontakt',
                'mail_forward_to'  => 'info',
            ],
        ]);

        // Step 1: create mailbox
        RecipeAction::create([
            'recipe_id'  => $recipe->id,
            'type'       => 'add_mailaccount',
            'order'      => 1,
            'parameters' => [
                'domain_name'  => '{domain_name}',
                'mail_adresses'=> '{mail_account}@{domain_name}',
                'mail_password'=> '{default_password}',
                'mail_quota_rule'=> '{mail_quota_mb}',
            ],
        ]);

        // Step 2: create forwarder
        RecipeAction::create([
            'recipe_id'  => $recipe->id,
            'type'       => 'add_mail_forward',
            'order'      => 2,
            'parameters' => [
                'domain_name'             => '{domain_name}',
                'mail_forward_address'    => '{mail_forward_from}@{domain_name}',
                'mail_forward_targets'    => '{mail_forward_to}@{domain_name}',
            ],
        ]);

        echo "✅ Recipe '{$name}' created (id: {$recipe->id})\n";
    }
}
