<?php
/**
 * R3D KAS Manager – Client Session Config
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.10.3-alpha
 * @date      2025-09-29
 * @license   MIT License
 */

// app/Http/Middleware/ClientSessionConfig.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class ClientSessionConfig
{
    public function handle($request, Closure $next)
    {
        Config::set('session.cookie', env('SESSION_COOKIE_CLIENT', 'kas_client_session'));
        Config::set('session.path', '/');
        Config::set('session.domain', env('SESSION_DOMAIN', '.r3d-kas-manager.test'));

        return $next($request);
    }
}
