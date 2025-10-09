<?php
/**
 * Seeder: RecipesSeeder
 *
 * Legt ein praktisches Test-Recipe an, das du mit --dryrun durchspielen kannst.
 *
 * @version 0.24.0
 * @date    2025-10-09
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Recipe;
use Carbon\Carbon;

class RecipesSeeder extends Seeder
{
    public function run(): void
    {
        // sauber idempotent: vorhandene Test-Rezepte entfernen
        DB::table('recipe_actions')->whereIn('recipe_id', function ($q) {
            $q->select('id')->from('recipes')->where('name', 'Test: Create domain + DNS + mail');
        })->delete();

        DB::table('recipes')->where('name', 'Test: Create domain + DNS + mail')->delete();

        // Erstelle das Recipe
        $recipe = Recipe::create([
            'name' => 'Test: Create domain + DNS + mail',
            'description' => 'Test-Recipe: add domain, apply DNS template, create mailbox, create forwarder (for dry-run).',
            'is_template' => false,
            'created_from' => null,
            'version' => 1,
            'category' => 'composite',
            // default_template_id optional — falls vorhanden, kann genutzt werden
            'default_template_id' => null,
            'php_version' => '8.3',
            'enable_ssl' => false,
            'expected_runtime' => 120,
            'variables' => [
                'default_password' => 'changeme',
                'mail_quota_mb' => 1024,
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Actions: order matters
        // 0: add_domain
        $recipe->actions()->create([
            'type' => 'add_domain',
            'parameters' => [
                // domain_name kann beim Run via --domain gesetzt werden; placeholder hier
                'domain_name' => '{domain_name}',
                'php_version' => '{php_version}', // ersetzt durch recipe/vars
                'domain_path' => '/web/',
                'domain_redirect_status' => 0
            ],
            'order' => 0,
            'label' => 'Add domain',
        ]);

        // 1: apply_template -> dns standard-web
        $recipe->actions()->create([
            'type' => 'apply_template',
            'parameters' => [
                'template_type' => 'dns',
                'template_name' => 'standard-web',
                // domain_name as placeholder
                'domain_name' => '{domain_name}'
            ],
            'order' => 1,
            'label' => 'Apply DNS template (standard-web)',
        ]);

        // 2: create_mailaccount -> info@{domain}
        $recipe->actions()->create([
            'type' => 'create_mailaccount',
            'parameters' => [
                'mail_login' => null,
                'mail_adresses' => 'info@{domain_name}',
                // mail_password can be set to 'auto' or provided in vars
                'mail_password' => '{default_password}',
                'quota_rule' => '{mail_quota_mb}'
            ],
            'order' => 2,
            'label' => 'Create mailbox info@',
        ]);

        // 3: create_forwarders -> forward formular@ to info@
        $recipe->actions()->create([
            'type' => 'create_forwarders',
            'parameters' => [
                // single item shortcut
                'source' => 'formular@{domain_name}',
                'targets' => 'info@{domain_name}',
                'spamfilter' => 'pdw'
            ],
            'order' => 3,
            'label' => 'Create forwarder formular@ -> info@',
        ]);

        $this->command->info("✅ Test-Recipe 'Test: Create domain + DNS + mail' created (id: {$recipe->id}).");
    }
}
