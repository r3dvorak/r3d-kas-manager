<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminConfigWorkspaceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_workspace_and_launch_runtime_settings(): void
    {
        $admin = User::create([
            'name' => 'Config Admin',
            'login' => 'config_admin',
            'email' => 'config_admin@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        $response = $this->actingAs($admin, 'web')->post('/config', [
            'workspace_isolation_enabled' => '1',
            'workspace_max_active' => 12,
            'workspace_limit_strategy' => 'reuse_existing',
            'workspace_idle_timeout_minutes' => 240,
            'workspace_absolute_lifetime_hours' => 48,
            'impersonation_workspace_buffer' => 3,
            'external_launch_token_ttl' => 90,
            'logout_scope_default' => 'all',
            'audit_log_level' => 'verbose',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertSame('1', AppSetting::getValue('workspace_isolation_enabled'));
        $this->assertSame('12', AppSetting::getValue('workspace_max_active'));
        $this->assertSame('reuse_existing', AppSetting::getValue('workspace_limit_strategy'));
        $this->assertSame('240', AppSetting::getValue('workspace_idle_timeout_minutes'));
        $this->assertSame('48', AppSetting::getValue('workspace_absolute_lifetime_hours'));
        $this->assertSame('3', AppSetting::getValue('impersonation_workspace_buffer'));
        $this->assertSame('90', AppSetting::getValue('external_launch_token_ttl'));
        $this->assertSame('all', AppSetting::getValue('logout_scope_default'));
        $this->assertSame('verbose', AppSetting::getValue('audit_log_level'));
    }
}
