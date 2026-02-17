<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_route(): void
    {
        $response = $this->get('/docs');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_user_gets_forbidden_on_admin_route(): void
    {
        $user = User::create([
            'name' => 'Editor',
            'login' => 'editor',
            'email' => 'editor@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'is_admin' => 0,
        ]);

        $response = $this->actingAs($user, 'web')->get('/docs');

        $response->assertForbidden();
    }

    public function test_admin_user_can_access_admin_route(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'login' => 'admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $response = $this->actingAs($admin, 'web')->get('/docs');

        $response->assertOk();
    }

    public function test_admin_can_login_with_login_field(): void
    {
        User::create([
            'name' => 'Admin',
            'login' => 'adminlogin',
            'email' => 'adminlogin@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $response = $this->post('/login', [
            'login' => 'adminlogin',
            'password' => 'secret123',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticated('web');
    }

    public function test_admin_can_create_kas_client_and_validation_works(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'login' => 'admin2',
            'email' => 'admin2@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $validResponse = $this->actingAs($admin, 'web')->post('/kas-clients', [
            'account_comment' => 'Client A',
            'account_login' => 'w01testa',
            'account_contact_mail' => 'clienta@example.test',
        ]);

        $validResponse->assertRedirect('/kas-clients');
        $this->assertDatabaseHas('kas_clients', [
            'account_login' => 'w01testa',
            'account_comment' => 'Client A',
        ]);

        $invalidResponse = $this->actingAs($admin, 'web')->from('/kas-clients/create')->post('/kas-clients', [
            'account_comment' => 'Client B',
            'account_contact_mail' => 'clientb@example.test',
        ]);

        $invalidResponse->assertRedirect('/kas-clients/create');
        $invalidResponse->assertSessionHasErrors(['account_login']);
        $this->assertDatabaseMissing('kas_clients', [
            'account_comment' => 'Client B',
        ]);
    }

    public function test_non_admin_cannot_create_kas_client(): void
    {
        $user = User::create([
            'name' => 'User',
            'login' => 'basicuser',
            'email' => 'basicuser@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'is_admin' => 0,
        ]);

        $response = $this->actingAs($user, 'web')->post('/kas-clients', [
            'account_comment' => 'Blocked',
            'account_login' => 'w01block',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('kas_clients', 0);
    }
}
