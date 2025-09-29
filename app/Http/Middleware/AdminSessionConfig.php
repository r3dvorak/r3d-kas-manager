<?php
/**
 * R3D KAS Manager – Admin Session Config
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.9.1-alpha
 * @date      2025-09-29
 * @license   MIT License
 */

namespace App\Http\Middleware;

use Closure;

class AdminSessionConfig
{
    public function handle($request, Closure $next)
    {
        // Cookie-Name für Admin setzen
        config(['session.cookie' => env('SESSION_COOKIE_WEB', 'kas_admin_session')]);

        return $next($request);
    }
}
