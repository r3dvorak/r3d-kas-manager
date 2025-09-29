<?php
/**
 * R3D KAS Manager – Unified Login Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.7.5-alpha
 * @date      2025-09-27
 * @license   MIT License
 * 
 * app\Http\Middleware\UseGuardSession.php
 */

namespace App\Http\Middleware;

use Closure;

class UseGuardSession
{
    public function handle($request, Closure $next, $guard = null)
    {
        logger('UseGuardSession fired for guard='.$guard);
        
        if ($guard === 'kas_client') {
            config(['session.cookie' => env('SESSION_COOKIE_CLIENT', 'kas_client_session')]);
        } elseif ($guard === 'web') {
            config(['session.cookie' => env('SESSION_COOKIE_WEB', 'kas_admin_session')]);
        }

        return $next($request);
    }
}
