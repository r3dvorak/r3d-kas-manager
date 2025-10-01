<?php
/**
 * R3D KAS Manager – Web Routes
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.10.3-alpha
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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  ...$guards
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next, ...$guards)
    {
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard === 'kas_client'
                    ? redirect()->route('client.dashboard')
                    : redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}

