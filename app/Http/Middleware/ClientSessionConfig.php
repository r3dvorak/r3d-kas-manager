<?php
/**
 * R3D KAS Manager – Client Session Config
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.9.1-alpha
 * @date      2025-09-29
 * @license   MIT License
 */

namespace App\Http\Middleware;

use Closure;

class ClientSessionConfig
{
    public function handle($request, Closure $next)
    {
        // Cookie-Name für Client setzen
        config(['session.cookie' => env('SESSION_COOKIE_CLIENT', 'kas_client_session')]);

        return $next($request);
    }
}
