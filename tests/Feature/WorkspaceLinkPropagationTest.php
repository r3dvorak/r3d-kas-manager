<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WorkspaceLinkPropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_w_helper_propagates_workspace_in_links(): void
    {
        Route::middleware('web')->get('/_workspace_routew_probe', function (Request $request) {
            return response()->json([
                'workspace' => $request->attributes->get('workspace'),
                'login_url' => route_w('login'),
            ]);
        });

        $workspace = str_repeat('a', 40);
        $response = $this->get('/_workspace_routew_probe?w=' . $workspace);
        $response->assertOk();
        $response->assertJsonPath('workspace', $workspace);

        $url = (string) $response->json('login_url');
        $this->assertStringContainsString('/login?w=' . $workspace, $url);
    }

    public function test_internal_redirects_keep_workspace_context(): void
    {
        Route::middleware('web')->get('/_workspace_redirect_probe', function () {
            return redirect()->route('login');
        });

        $workspace = str_repeat('b', 40);
        $response = $this->get('/_workspace_redirect_probe?w=' . $workspace);
        $response->assertRedirect('/login?w=' . $workspace);
    }

    public function test_logout_redirect_keeps_workspace_context(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'login' => 'admin_redirect_ws',
            'email' => 'admin_redirect_ws@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $workspace = str_repeat('c', 40);
        $response = $this->actingAs($user, 'web')->post('/logout?w=' . $workspace);
        $response->assertRedirect('/login?w=' . $workspace);
    }
}
