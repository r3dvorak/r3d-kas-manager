<?php
/**
 * R3D KAS Manager – Route Service Provider
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.12.1-alpha
 * @date      2025-10-05
 *
 * @copyright (C) 2025
 * @license   MIT License
 *
 * Handles route registration and post-login redirection logic.
 */

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Default admin home route.
     */
    public const HOME = '/dashboard';

    /**
     * Default client home route.
     */
    public const CLIENT_HOME = '/client/dashboard';

    /**
     * Determine where to redirect users after login
     * depending on their guard or role.
     */
    public static function redirectTo(): string
    {
        if (Auth::guard('web')->check()) {
            return static::HOME;
        }

        if (Auth::guard('kas_client')->check()) {
            return static::CLIENT_HOME;
        }

        // Fallback (guest or invalid session)
        return '/login';
    }

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
