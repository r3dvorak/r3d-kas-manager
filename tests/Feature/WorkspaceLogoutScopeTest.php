<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Tests\TestCase;

class WorkspaceLogoutScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_with_scope_all_forgets_workspace_session_cookies(): void
    {
        $this->withoutMiddleware(EncryptCookies::class);

        $user = User::create([
            'name' => 'Admin',
            'login' => 'admin_scope_all',
            'email' => 'admin_scope_all@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $wa = str_repeat('a', 40);
        $wb = str_repeat('b', 40);

        $response = $this
            ->actingAs($user, 'web')
            ->withCookies([
                'laravel_workspace_session_' . $wa => 'cookie-a',
                'laravel_workspace_session_' . $wb => 'cookie-b',
            ])
            ->post('/logout?w=' . $wa, [
                'scope' => 'all',
            ]);

        $response->assertRedirect('/login?w=' . $wa);

        $setCookieNames = collect($response->headers->getCookies())
            ->map(fn ($cookie) => (string) $cookie->getName())
            ->all();

        $this->assertContains('laravel_workspace_session_' . $wa, $setCookieNames);
        $this->assertContains('laravel_workspace_session_' . $wb, $setCookieNames);
    }

    public function test_default_logout_does_not_forget_unrelated_workspace_cookie(): void
    {
        $this->withoutMiddleware(EncryptCookies::class);

        $user = User::create([
            'name' => 'Admin',
            'login' => 'admin_scope_current',
            'email' => 'admin_scope_current@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $wa = str_repeat('c', 40);
        $wb = str_repeat('d', 40);

        $response = $this
            ->actingAs($user, 'web')
            ->withCookies([
                'laravel_workspace_session_' . $wa => 'cookie-a',
                'laravel_workspace_session_' . $wb => 'cookie-b',
            ])
            ->post('/logout?w=' . $wa);

        $response->assertRedirect('/login?w=' . $wa);

        $setCookieNames = collect($response->headers->getCookies())
            ->map(fn ($cookie) => (string) $cookie->getName())
            ->all();

        $this->assertNotContains('laravel_workspace_session_' . $wb, $setCookieNames);
    }
}
