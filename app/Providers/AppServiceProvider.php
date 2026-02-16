<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.6.8-alpha
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
use Illuminate\Support\Facades\Schema;
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
        // Always provide a safe default, then override from DB when available.
        config([
            'r3d.session_timeout' => 30,
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
}
