<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminRecipeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_recipe_with_actions(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'login' => 'recipe_admin',
            'email' => 'recipe_admin@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $store = $this->actingAs($admin, 'web')->post('/recipes', [
            'name' => 'Standard Joomla Setup',
            'description' => 'Provisioning baseline',
            'category' => 'composite',
            'status' => 'draft',
            'is_template' => 1,
            'variables_json' => '{"php_version":"8.3"}',
            'actions' => [
                ['order' => 1, 'type' => 'set_php_version', 'label' => 'Set PHP', 'parameters_json' => '{"version":"8.3"}'],
                ['order' => 2, 'type' => 'enable_ssl', 'label' => 'Enable SSL', 'parameters_json' => '{}'],
            ],
        ]);

        $store->assertRedirect('/recipes');
        $this->assertDatabaseHas('recipes', ['name' => 'Standard Joomla Setup', 'status' => 'draft']);
        $recipe = Recipe::where('name', 'Standard Joomla Setup')->firstOrFail();
        $this->assertDatabaseCount('recipe_actions', 2);

        $update = $this->actingAs($admin, 'web')->put('/recipes/' . $recipe->id, [
            'name' => 'Standard Joomla Setup',
            'description' => 'Updated description',
            'category' => 'composite',
            'status' => 'active',
            'is_template' => 0,
            'variables_json' => '{"php_version":"8.3","ssl":true}',
            'actions' => [
                ['order' => 1, 'type' => 'set_php_version', 'label' => 'Set PHP', 'parameters_json' => '{"version":"8.3"}'],
            ],
        ]);

        $update->assertRedirect('/recipes/' . $recipe->id);
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'status' => 'active', 'version' => 2]);
        $this->assertDatabaseCount('recipe_actions', 1);

        $delete = $this->actingAs($admin, 'web')->delete('/recipes/' . $recipe->id);
        $delete->assertRedirect('/recipes');
        $this->assertSoftDeleted('recipes', ['id' => $recipe->id]);
    }

    public function test_admin_can_trigger_dry_run_and_apply_run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'login' => 'recipe_runner',
            'email' => 'recipe_runner@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $recipe = Recipe::create([
            'name' => 'PHP Upgrade',
            'status' => 'active',
            'version' => 1,
            'variables' => ['php_version' => '8.3'],
        ]);

        $recipe->actions()->create([
            'type' => 'unknown_action',
            'order' => 1,
            'parameters' => ['value' => 'x'],
        ]);

        $dryRun = $this->actingAs($admin, 'web')->post('/recipes/' . $recipe->id . '/run-dry', [
            'kas_login' => 'w01testa',
            'domain_name' => 'example.test',
            'variables_json' => '{"foo":"bar"}',
        ]);
        $dryRun->assertStatus(302);
        $this->assertDatabaseCount('recipe_runs', 1);

        $apply = $this->actingAs($admin, 'web')->post('/recipes/' . $recipe->id . '/run-apply', [
            'kas_login' => 'w01testa',
            'domain_name' => 'example.test',
        ]);
        $apply->assertStatus(302);
        $this->assertDatabaseCount('recipe_runs', 2);
    }

    public function test_non_admin_cannot_access_admin_recipe_routes(): void
    {
        $user = User::create([
            'name' => 'User',
            'login' => 'recipe_user',
            'email' => 'recipe_user@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'is_admin' => 0,
        ]);

        $response = $this->actingAs($user, 'web')->get('/recipes');
        $response->assertForbidden();
    }
}

