<?php
/**
 * R3D KAS Manager – Seeder: Add Domain + DNS (mail-only)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.25.0-alpha
 * @date      2025-10-10
 * @license   MIT License
 *
 * Creates a reusable recipe for mail-only domains:
 * - Adds domain with minimal settings
 * - Applies DNS template "mail-only"
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\RecipeAction;
use App\Models\KasTemplate;

class RecipesMailOnlySeeder extends Seeder
{
    public function run(): void
    {
        // 🔍 find the DNS template "mail-only"
        $dnsTemplate = KasTemplate::where('template_name', 'mail-only')
            ->where('template_type', 'dns')
            ->first();

        if (!$dnsTemplate) {
            $this->command->error('DNS Template "mail-only" not found. Please seed kas_templates first.');
            return;
        }

        // 🧹 remove any old recipe with same name
        Recipe::where('name', 'Add Domain + DNS (mail-only)')->delete();

        // 🆕 create the recipe
        $recipe = Recipe::create([
            'name' => 'Add Domain + DNS (mail-only)',
            'description' => 'Creates a new mail-only domain and applies mail DNS template.',
            'is_template' => false,
            'version' => 1,
            'category' => 'mail-only',
            'default_template_id' => $dnsTemplate->id,
            'php_version' => null,
            'enable_ssl' => false,
            'expected_runtime' => 60,
            'variables' => [
                'domain_name' => null,
                'kas_login' => null,
            ],
        ]);

        // 1️⃣ Add Domain (basic)
        RecipeAction::create([
            'recipe_id' => $recipe->id,
            'type' => 'add_domain',
            'parameters' => [
                'domain_name' => '{domain_name}',
                'php_version' => null,
                'domain_path' => '/mailonly/',
            ],
            'order' => 1,
        ]);

        // 2️⃣ Apply DNS template (mail-only)
        RecipeAction::create([
            'recipe_id' => $recipe->id,
            'type' => 'update_dns_records',
            'parameters' => [
                'template_id' => $dnsTemplate->id,
                'domain_name' => '{domain_name}',
            ],
            'order' => 2,
        ]);

        $this->command->info('✅ Recipe "Add Domain + DNS (mail-only)" created (id: ' . $recipe->id . ')');
    }
}
