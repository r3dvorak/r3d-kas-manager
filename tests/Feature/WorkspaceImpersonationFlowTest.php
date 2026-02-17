<?php

namespace Tests\Feature;

use App\Models\ImpersonationToken;
use App\Models\KasClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkspaceImpersonationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_client_logins_can_coexist_in_separate_workspaces(): void
    {
        User::create([
            'name' => 'Admin',
            'login' => 'admin_ws',
            'email' => 'admin_ws@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $client = KasClient::create([
            'account_login' => 'wclientws',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Client WS',
        ]);

        $wa = str_repeat('a', 40);
        $wb = str_repeat('b', 40);

        $adminLogin = $this->post('/login?w=' . $wa, [
            'login' => 'admin_ws',
            'password' => 'secret123',
        ]);
        $adminLogin->assertRedirect('/?w=' . $wa);

        $clientLogin = $this->post('/login?w=' . $wb, [
            'login' => (string) $client->account_login,
            'password' => 'secret123',
        ]);
        $clientLogin->assertRedirect('/client/dashboard?w=' . $wb);

        $adminArea = $this->get('/?w=' . $wa);
        $adminArea->assertOk();

        $clientArea = $this->get('/client/dashboard?w=' . $wb);
        $clientArea->assertOk();
    }

    public function test_impersonation_redirect_uses_fresh_workspace_key(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'login' => 'admin_imp',
            'email' => 'admin_imp@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $client = KasClient::create([
            'account_login' => 'wimp001',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Impersonation Client',
        ]);

        $adminWorkspace = str_repeat('c', 40);
        $response = $this->actingAs($admin, 'web')->get('/kas-clients/' . $client->id . '/impersonate?w=' . $adminWorkspace);
        $response->assertRedirect();

        $location = (string) $response->headers->get('Location');
        $this->assertMatchesRegularExpression('#/impersonate/.+\?w=[a-f0-9]{40}$#', $location);
        $this->assertStringNotContainsString('?w=' . $adminWorkspace, $location);
    }

    public function test_leave_impersonation_restores_admin_in_same_workspace(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'login' => 'admin_leave',
            'email' => 'admin_leave@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $client = KasClient::create([
            'account_login' => 'wleave01',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Leave Client',
        ]);

        $workspace = str_repeat('d', 40);

        $leave = $this
            ->actingAs($client, 'kas_client')
            ->withSession([
                'impersonate' => true,
                'impersonate_admin_id' => (int) $admin->id,
            ])
            ->post('/kas-clients/impersonate/leave?w=' . $workspace);
        $leave->assertRedirect('/?w=' . $workspace);
        $adminArea = $this->get('/?w=' . $workspace);
        $adminArea->assertOk();
    }

    public function test_consume_impersonation_sets_admin_return_context(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'login' => 'admin_consume',
            'email' => 'admin_consume@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $client = KasClient::create([
            'account_login' => 'wconsume1',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Consume Client',
        ]);

        $token = ImpersonationToken::generateForClient((int) $client->id, (int) $admin->id);
        $workspace = str_repeat('e', 40);

        $consume = $this->get('/impersonate/' . $token->getRawToken() . '?w=' . $workspace);
        $consume->assertRedirect('/client/dashboard?w=' . $workspace);
        $consume->assertSessionHas('impersonate', true);
        $consume->assertSessionHas('impersonate_admin_id', (int) $admin->id);
    }

}
