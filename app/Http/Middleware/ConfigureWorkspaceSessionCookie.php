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
 * Runs before StartSession and configures the session cookie name strictly
 * from workspace query (?w=...). This prevents implicit workspace reuse when
 * opening a fresh tab without a workspace parameter.
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
        if (ResolveWorkspace::isValidWorkspace($workspace)) {
            config(['session.cookie' => $base . '_' . $workspace]);
        }

        return $next($request);
    }
}
