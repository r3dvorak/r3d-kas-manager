<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.28.22-alpha
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

        $workspaceIsolationEnv = filter_var((string) env('WORKSPACE_ISOLATION_ENABLED', 'true'), FILTER_VALIDATE_BOOL);
        $workspaceMaxActiveEnv = max(1, (int) env('WORKSPACE_MAX_ACTIVE', 10));
        $externalLaunchTokenTtlEnv = max(15, (int) env('EXTERNAL_LAUNCH_TOKEN_TTL', 60));

        // Always provide safe defaults, then override from DB when available.
        config([
            'r3d.session_timeout' => 30,
            'r3d.absolute_session_max' => 180,
            'r3d.workspace_isolation_enabled' => $workspaceIsolationEnv,
            'r3d.workspace_max_active' => $workspaceMaxActiveEnv,
            'r3d.workspace_limit_strategy' => 'reuse_existing',
            'r3d.workspace_idle_timeout_minutes' => 300,
            'r3d.workspace_absolute_lifetime_hours' => 24,
            'r3d.impersonation_workspace_buffer' => 2,
            'r3d.external_launch_token_ttl' => $externalLaunchTokenTtlEnv,
            'r3d.logout_scope_default' => 'current',
            'r3d.audit_log_level' => 'standard',
        ]);

        try {
            if (Schema::hasTable('app_settings')) {
                config([
                    'r3d.session_timeout' => (int) AppSetting::getValue('session_timeout', 30),
                    'r3d.absolute_session_max' => (int) AppSetting::getValue('absolute_session_max', 180),
                    'r3d.workspace_isolation_enabled' => $this->settingBool('workspace_isolation_enabled', $workspaceIsolationEnv),
                    'r3d.workspace_max_active' => max(1, (int) AppSetting::getValue('workspace_max_active', $workspaceMaxActiveEnv)),
                    'r3d.workspace_limit_strategy' => $this->settingString('workspace_limit_strategy', 'reuse_existing', ['reuse_existing', 'allow_new']),
                    'r3d.workspace_idle_timeout_minutes' => max(5, (int) AppSetting::getValue('workspace_idle_timeout_minutes', 300)),
                    'r3d.workspace_absolute_lifetime_hours' => max(1, (int) AppSetting::getValue('workspace_absolute_lifetime_hours', 24)),
                    'r3d.impersonation_workspace_buffer' => max(0, (int) AppSetting::getValue('impersonation_workspace_buffer', 2)),
                    'r3d.external_launch_token_ttl' => max(15, (int) AppSetting::getValue('external_launch_token_ttl', $externalLaunchTokenTtlEnv)),
                    'r3d.logout_scope_default' => $this->settingString('logout_scope_default', 'current', ['current', 'all']),
                    'r3d.audit_log_level' => $this->settingString('audit_log_level', 'standard', ['minimal', 'standard', 'verbose']),
                ]);
            }
        } catch (Throwable $e) {
            // DB may be unavailable in tooling contexts (IDE indexers, CI bootstrap, etc.).
            // Keep boot non-fatal and continue with defaults.
        }
    }

    private function settingBool(string $key, bool $default): bool
    {
        $raw = AppSetting::getValue($key, $default ? '1' : '0');

        return in_array(strtolower((string) $raw), ['1', 'true', 'on', 'yes'], true);
    }

    private function settingString(string $key, string $default, array $allowed): string
    {
        $value = strtolower(trim((string) AppSetting::getValue($key, $default)));

        return in_array($value, $allowed, true) ? $value : $default;
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
