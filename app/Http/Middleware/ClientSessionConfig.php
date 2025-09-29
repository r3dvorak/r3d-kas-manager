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
 * app\Http\Middleware\ClientSessionConfig.php
 */

namespace App\Http\Middleware;

use Closure;

class ClientSessionConfig
{
    public function handle($request, Closure $next)
    {
        config(['session.cookie' => env('SESSION_COOKIE_CLIENT', 'kas_client_session')]);
        return $next($request);
    }
}
