<?php
/**
 * R3D KAS Manager – UseGuardSession Middleware
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.28.20-alpha
 * @date      2025-10-05
 * @license   MIT License
 * 
 * Ensures separate session cookies for each authentication guard.
 * 
 * app/Http/Middleware/UseGuardSession.php
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UseGuardSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $guard = null)
    {
        $workspace = strtolower((string) ($request->attributes->get('workspace') ?? $request->query('w', '')));

        if (workspace_isolation_enabled() && \App\Http\Middleware\ResolveWorkspace::isValidWorkspace($workspace)) {
            $base = (string) env('SESSION_COOKIE_WORKSPACE', Str::slug((string) env('APP_NAME', 'laravel'), '_') . '_workspace_session');
            config(['session.cookie' => $base . '_' . $workspace]);
        } else {
            // Legacy fallback without workspace context.
            if ($guard === 'kas_client') {
                config(['session.cookie' => env('SESSION_COOKIE_CLIENT', 'kas_client_session')]);
            } elseif ($guard === 'web') {
                config(['session.cookie' => env('SESSION_COOKIE_WEB', 'kas_admin_session')]);
            }
        }

        return $next($request);
    }
}
