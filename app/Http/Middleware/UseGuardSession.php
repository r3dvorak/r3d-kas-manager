<?php
/**
 * R3D KAS Manager – UseGuardSession Middleware
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.12.1-alpha
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
        if ($guard === 'kas_client') {
            config(['session.cookie' => env('SESSION_COOKIE_CLIENT', 'kas_client_session')]);
        } elseif ($guard === 'web') {
            config(['session.cookie' => env('SESSION_COOKIE_WEB', 'kas_admin_session')]);
        }

        return $next($request);
    }
}
