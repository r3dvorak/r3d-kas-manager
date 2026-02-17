<?php
/**
 * R3D KAS Manager - Configure Workspace Session Cookie
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.0-alpha
 * @date      2026-02-17
 * @license   MIT License
 *
 * Runs before StartSession and configures the session cookie name based on
 * workspace query (?w=...) or an existing workspace cookie on the request.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConfigureWorkspaceSessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        $base = (string) env('SESSION_COOKIE_WORKSPACE', Str::slug((string) env('APP_NAME', 'laravel'), '_') . '_workspace_session');

        $workspace = strtolower((string) $request->query(ResolveWorkspace::QUERY_KEY, ''));
        if (!ResolveWorkspace::isValidWorkspace($workspace)) {
            $workspace = $this->workspaceFromCookieBag($request, $base) ?? '';
        }

        if ($workspace !== '') {
            config(['session.cookie' => $base . '_' . $workspace]);
        }

        return $next($request);
    }

    private function workspaceFromCookieBag(Request $request, string $base): ?string
    {
        $matches = [];
        $pattern = '/^' . preg_quote($base, '/') . '_([a-f0-9]{40})$/';

        foreach (array_keys($request->cookies->all()) as $cookieName) {
            if (preg_match($pattern, (string) $cookieName, $m) === 1) {
                $matches[] = strtolower((string) $m[1]);
            }
        }

        if (count($matches) === 1 && ResolveWorkspace::isValidWorkspace($matches[0])) {
            return $matches[0];
        }

        return null;
    }
}

