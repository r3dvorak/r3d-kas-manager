<?php
/**
 * R3D KAS Manager – Web Routes
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.10.4-alpha
 * @date      2025-09-30
 * 
 * @license   MIT License
 * @copyright (C) 2025
 * 
 * app\Http\Middleware\RedirectIfAuthenticated.php
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     */
    public function handle($request, Closure $next, ...$guards)
    {
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check() && $request->routeIs('login*')) {
                return $guard === 'kas_client'
                    ? redirect()->route('client.dashboard')
                    : redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
