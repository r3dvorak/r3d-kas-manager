<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.28.20-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * AuthServiceProvider with impersonation Gate
 */

namespace App\Providers;

use Throwable;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\AppSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();

        // Always provide a safe default, then override from DB when available.
        config([
            'r3d.session_timeout' => 30,
            'r3d.workspace_isolation_enabled' => filter_var((string) env('WORKSPACE_ISOLATION_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
        ]);

        try {
            if (Schema::hasTable('app_settings')) {
                config([
                    'r3d.session_timeout' => AppSetting::getValue('session_timeout', 30),
                ]);
            }
        } catch (Throwable $e) {
            // DB may be unavailable in tooling contexts (IDE indexers, CI bootstrap, etc.).
            // Keep boot non-fatal and continue with defaults.
        }
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $login = strtolower((string) $request->input('login', ''));
            return Limit::perMinute(10)->by($request->ip() . '|' . $login);
        });

        RateLimiter::for('launch-create', function (Request $request) {
            $clientId = (string) optional($request->user('kas_client'))->id;
            return Limit::perMinute(20)->by($request->ip() . '|' . $clientId);
        });

        RateLimiter::for('launch-consume', function (Request $request) {
            $token = (string) $request->route('token');
            return Limit::perMinute(30)->by($request->ip() . '|' . substr($token, 0, 16));
        });

        RateLimiter::for('impersonate-generate', function (Request $request) {
            $adminId = (string) optional($request->user('web'))->id;
            return Limit::perMinute(30)->by($request->ip() . '|' . $adminId);
        });

        RateLimiter::for('impersonate-consume', function (Request $request) {
            $token = (string) $request->route('token');
            return Limit::perMinute(30)->by($request->ip() . '|' . substr($token, 0, 16));
        });
    }
}
