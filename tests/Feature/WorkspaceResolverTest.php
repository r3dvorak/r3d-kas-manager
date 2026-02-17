<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WorkspaceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_to_workspace_when_missing(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertMatchesRegularExpression('#/login\?w=[a-f0-9]{40}$#', (string) $location);
    }

    public function test_valid_workspace_query_does_not_redirect_and_renders_page(): void
    {
        $workspace = str_repeat('a', 40);

        $response = $this->get('/login?w=' . $workspace);

        $response->assertOk();
        $response->assertSee('R3D KAS Manager Login');
    }

    public function test_invalid_workspace_is_replaced_and_flashes_warning(): void
    {
        $response = $this->get('/login?w=invalid');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertMatchesRegularExpression('#/login\?w=[a-f0-9]{40}$#', (string) $location);
        $response->assertSessionHas('warning');
    }

    public function test_workspace_context_is_injected_into_request_and_container(): void
    {
        Route::middleware('web')->get('/_workspace_probe', function (Request $request) {
            return response()->json([
                'request' => $request->attributes->get('workspace'),
                'container' => app('workspace.key'),
                'view' => view()->shared('workspaceKey'),
            ]);
        });

        $workspace = str_repeat('b', 40);
        $response = $this->get('/_workspace_probe?w=' . $workspace);

        $response->assertOk();
        $response->assertJson([
            'request' => $workspace,
            'container' => $workspace,
            'view' => $workspace,
        ]);
    }
}
