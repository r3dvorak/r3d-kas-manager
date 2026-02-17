<?php
/**
 * R3D KAS Manager - Resolve Workspace Middleware
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.0-alpha
 * @date      2026-02-17
 * @license   MIT License
 *
 * Resolves a workspace key from query string (?w=...), configures a
 * workspace-specific session cookie, injects context into request/container/view,
 * and canonicalizes GET/HEAD login URLs to always carry w.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class ResolveWorkspace
{
    public const QUERY_KEY = 'w';
    public const LENGTH = 40; // 160-bit hex

    public function handle(Request $request, Closure $next)
    {
        $provided = (string) $request->query(self::QUERY_KEY, '');
        $normalized = strtolower(trim($provided));
        $isValid = $this->isValidWorkspace($normalized);
        $isSafeMethod = in_array($request->method(), ['GET', 'HEAD'], true);
        $isLoginRoute = $request->routeIs('login');
        $isAuthenticated = Auth::guard('web')->check() || Auth::guard('kas_client')->check();

        // Workspace generation is restricted to public login canonicalization only.
        $workspace = null;
        if ($isValid) {
            $workspace = $normalized;
        } else {
            $fromCookieConfig = $this->workspaceFromConfiguredCookie();
            if ($fromCookieConfig !== null) {
                $workspace = $fromCookieConfig;
            } elseif ($isSafeMethod && $isLoginRoute && !$isAuthenticated) {
                $workspace = $this->generateWorkspace();
            }
        }

        // Expose context early for controllers/views/services.
        $request->attributes->set('workspace', $workspace);
        if (is_string($workspace) && $workspace !== '') {
            app()->instance('workspace.key', $workspace);
            View::share('workspaceKey', $workspace);
            URL::defaults([self::QUERY_KEY => $workspace]);
        } else {
            View::share('workspaceKey', null);
        }

        // Canonicalize GET/HEAD URLs so workspace context remains visible.
        $needsCanonicalRedirect = $isSafeMethod
            && $isLoginRoute
            && !$isAuthenticated
            && is_string($workspace)
            && $workspace !== ''
            && ($provided === '' || $normalized !== $provided || !$isValid);

        if ($needsCanonicalRedirect) {
            $query = $request->query();
            $query[self::QUERY_KEY] = $workspace;

            return redirect()->to($request->url() . '?' . http_build_query($query));
        }

        return $next($request);
    }

    public static function current(Request $request): ?string
    {
        $w = $request->attributes->get('workspace');
        return is_string($w) && self::isValidWorkspace($w) ? $w : null;
    }

    public static function isValidWorkspace(string $workspace): bool
    {
        return (bool) preg_match('/^[a-f0-9]{40}$/', strtolower($workspace));
    }

    private function generateWorkspace(): string
    {
        return bin2hex(random_bytes(self::LENGTH / 2));
    }

    private function workspaceFromConfiguredCookie(): ?string
    {
        $cookie = (string) config('session.cookie', '');
        $base = (string) env('SESSION_COOKIE_WORKSPACE', Str::slug((string) env('APP_NAME', 'laravel'), '_') . '_workspace_session');
        $prefix = $base . '_';

        if (!str_starts_with($cookie, $prefix)) {
            return null;
        }

        $workspace = strtolower(substr($cookie, strlen($prefix)));
        return self::isValidWorkspace($workspace) ? $workspace : null;
    }
}
