<?php
/**
 * R3D KAS Manager - Resolve Workspace Middleware
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.22-alpha
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
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class ResolveWorkspace
{
    public const QUERY_KEY = 'w';
    public const LENGTH = 40; // 160-bit hex

    public function handle(Request $request, Closure $next)
    {
        if (!workspace_isolation_enabled()) {
            $request->attributes->set('workspace', null);
            View::share('workspaceKey', null);
            return $next($request);
        }

        $provided = (string) $request->query(self::QUERY_KEY, '');
        $normalized = strtolower(trim($provided));
        $isValid = $this->isValidWorkspace($normalized);
        $isSafeMethod = in_array($request->method(), ['GET', 'HEAD'], true);
        $isLoginRoute = $request->routeIs('login');
        $isAuthenticated = Auth::guard('web')->check() || Auth::guard('kas_client')->check();
        $knownWorkspaces = $this->knownWorkspacesFromCookies($request);
        $maxActive = max(1, (int) config('r3d.workspace_max_active', 10));
        $limitReached = count($knownWorkspaces) >= $maxActive;
        $limitStrategy = strtolower((string) config('r3d.workspace_limit_strategy', 'reuse_existing'));

        // Workspace generation is restricted to public login canonicalization only.
        $workspace = null;
        if ($isValid) {
            $workspace = $normalized;

            if ($isSafeMethod && $isLoginRoute && !$isAuthenticated && $limitReached && !in_array($workspace, $knownWorkspaces, true) && $limitStrategy === 'reuse_existing') {
                $workspace = Arr::first($knownWorkspaces);
            }
        } else {
            $fromReferer = $this->workspaceFromReferer($request);
            if ($fromReferer !== null && $isAuthenticated) {
                $workspace = $fromReferer;
            }

            $fromCookieConfig = $this->workspaceFromConfiguredCookie();
            if ($workspace === null && $fromCookieConfig !== null) {
                $workspace = $fromCookieConfig;
            } elseif ($workspace === null && $isSafeMethod && $isLoginRoute && !$isAuthenticated) {
                if ($limitReached && $limitStrategy === 'reuse_existing') {
                    $workspace = Arr::first($knownWorkspaces);
                } else {
                    $workspace = self::generateWorkspaceKey();
                }
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
            && !$isAuthenticated
            && is_string($workspace)
            && $workspace !== ''
            && $isLoginRoute
            && ($provided === '' || $normalized !== $provided || !$isValid || $normalized !== $workspace);

        $needsLegacyRedirect = $isSafeMethod
            && $isAuthenticated
            && !$isLoginRoute
            && is_string($workspace)
            && $workspace !== ''
            && ($provided === '' || $normalized !== $workspace);

        if ($needsCanonicalRedirect || $needsLegacyRedirect) {
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

    public static function generateWorkspaceKey(): string
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

    private function workspaceFromReferer(Request $request): ?string
    {
        $referer = (string) $request->headers->get('referer', '');
        if ($referer === '') {
            return null;
        }

        $parts = parse_url($referer);
        if (!is_array($parts) || (($parts['host'] ?? null) !== $request->getHost())) {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $workspace = strtolower((string) ($query[self::QUERY_KEY] ?? ''));
        return self::isValidWorkspace($workspace) ? $workspace : null;
    }

    /**
     * @return array<int,string>
     */
    private function knownWorkspacesFromCookies(Request $request): array
    {
        $base = (string) env(
            'SESSION_COOKIE_WORKSPACE',
            Str::slug((string) env('APP_NAME', 'laravel'), '_') . '_workspace_session'
        );
        $prefix = $base . '_';

        return collect(array_keys($request->cookies->all()))
            ->filter(fn (string $name) => str_starts_with($name, $prefix))
            ->map(fn (string $name) => strtolower(substr($name, strlen($prefix))))
            ->filter(fn (string $workspace) => self::isValidWorkspace($workspace))
            ->unique()
            ->values()
            ->all();
    }
}
