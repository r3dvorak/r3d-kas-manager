<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceSessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_cookie_name_contains_workspace_key(): void
    {
        $workspace = str_repeat('c', 40);
        $response = $this->get('/login?w=' . $workspace);

        $cookieName = $this->extractSessionCookieName($response->headers->getCookies());
        $expectedBase = (string) env('SESSION_COOKIE_WORKSPACE', Str::slug((string) env('APP_NAME', 'laravel'), '_') . '_workspace_session');

        $this->assertNotNull($cookieName);
        $this->assertSame($expectedBase . '_' . $workspace, $cookieName);
    }

    public function test_two_workspaces_use_different_session_cookie_names(): void
    {
        $wa = str_repeat('d', 40);
        $wb = str_repeat('e', 40);

        $a = $this->get('/login?w=' . $wa);
        $this->refreshApplication();
        $b = $this->get('/login?w=' . $wb);

        $cookieA = $this->extractSessionCookieName($a->headers->getCookies(), $wa);
        $cookieB = $this->extractSessionCookieName($b->headers->getCookies(), $wb);

        $this->assertNotNull($cookieA);
        $this->assertNotNull($cookieB);
        $this->assertNotSame($cookieA, $cookieB);
    }

    public function test_workspace_isolation_keeps_separate_session_values(): void
    {
        $this->registerProbeRoute();

        $wa = str_repeat('f', 40);
        $wb = str_repeat('1', 40);

        // Workspace A: set session value.
        $setA = $this->get('/_workspace_session_probe?w=' . $wa . '&set=alpha');
        $cookieA = $this->extractSessionCookie($setA->headers->getCookies(), $wa);
        $this->assertNotNull($cookieA);

        // Workspace A: value is visible with A cookie.
        $aRead = $this->withCookie($cookieA['name'], $cookieA['value'])->get('/_workspace_session_probe?w=' . $wa);
        $aRead->assertOk();
        $aRead->assertJson(['probe' => 'alpha']);

        $this->refreshApplication();
        $this->registerProbeRoute();

        // Workspace B: no value yet with no B cookie.
        $bRead = $this->get('/_workspace_session_probe?w=' . $wb);
        $bRead->assertOk();
        $bRead->assertJson(['probe' => null]);
    }

    private function registerProbeRoute(): void
    {
        Route::middleware('web')->get('/_workspace_session_probe', function (Request $request) {
            if ($request->query('set') !== null) {
                session(['probe' => (string) $request->query('set')]);
            }

            return response()->json([
                'workspace' => $request->attributes->get('workspace'),
                'probe' => session('probe'),
            ]);
        });
    }

    /**
     * @param array<int,\Symfony\Component\HttpFoundation\Cookie> $cookies
     */
    private function extractSessionCookieName(array $cookies, ?string $workspace = null): ?string
    {
        $cookie = $this->extractSessionCookie($cookies, $workspace);
        return $cookie['name'] ?? null;
    }

    /**
     * @param array<int,\Symfony\Component\HttpFoundation\Cookie> $cookies
     * @return array{name:string,value:string}|null
     */
    private function extractSessionCookie(array $cookies, ?string $workspace = null): ?array
    {
        $suffix = $workspace ? '_' . strtolower($workspace) : null;

        if ($suffix !== null) {
            foreach ($cookies as $cookie) {
                $name = (string) $cookie->getName();
                if ($name !== 'XSRF-TOKEN' && str_ends_with(strtolower($name), $suffix)) {
                    return [
                        'name' => $cookie->getName(),
                        'value' => $cookie->getValue(),
                    ];
                }
            }
        }

        foreach ($cookies as $cookie) {
            if ($cookie->getName() !== 'XSRF-TOKEN') {
                return [
                    'name' => $cookie->getName(),
                    'value' => $cookie->getValue(),
                ];
            }
        }

        return null;
    }
}
