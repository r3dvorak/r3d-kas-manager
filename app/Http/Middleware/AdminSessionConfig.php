<?php
/**
 * R3D KAS Manager – Unified Login Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.9.0-alpha
 * @date      2025-09-27
 * @license   MIT License
 * 
 * app\Http\Middleware\AdminSessionConfig.php
 */

namespace App\Http\Middleware;

use Closure;

class AdminSessionConfig
{
    public function handle($request, Closure $next)
    {
        config(['session.cookie' => env('SESSION_COOKIE_WEB', 'kas_admin_session')]);
        return $next($request);
    }
}
