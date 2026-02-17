<?php

namespace Tests\Feature;

use App\Models\ExternalLaunchAudit;
use App\Models\ExternalLaunchToken;
use App\Models\ImpersonationToken;
use App\Models\KasClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkspaceCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_cleanup_command_deletes_stale_records_and_keeps_recent(): void
    {
        $client = KasClient::create([
            'account_login' => 'wcleanup1',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Cleanup Client',
        ]);

        $oldToken = ExternalLaunchToken::create([
            'token_hash' => hash('sha256', 'old-token'),
            'kas_client_id' => $client->id,
            'tool' => 'webmail',
            'target_url' => 'https://webmail.all-inkl.com/',
            'workspace_key' => str_repeat('a', 40),
            'expires_at' => now()->subDays(20),
            'used_at' => now()->subDays(19),
        ]);

        $recentToken = ExternalLaunchToken::create([
            'token_hash' => hash('sha256', 'recent-token'),
            'kas_client_id' => $client->id,
            'tool' => 'pma',
            'target_url' => 'https://wcleanup1.kasserver.com/mysqladmin/PMA5/index.php',
            'workspace_key' => str_repeat('b', 40),
            'expires_at' => now()->addMinutes(10),
        ]);

        $oldAudit = ExternalLaunchAudit::create([
            'external_launch_token_id' => $oldToken->id,
            'kas_client_id' => $client->id,
            'event' => 'created',
            'tool' => 'webmail',
            'workspace_key' => str_repeat('a', 40),
        ]);
        $oldAudit->forceFill([
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ])->save();

        ExternalLaunchAudit::create([
            'external_launch_token_id' => $recentToken->id,
            'kas_client_id' => $client->id,
            'event' => 'created',
            'tool' => 'pma',
            'workspace_key' => str_repeat('b', 40),
        ]);

        $oldImpersonation = ImpersonationToken::create([
            'token' => hash('sha256', 'old-imp'),
            'kas_client_id' => $client->id,
            'created_by' => null,
            'expires_at' => now()->subDays(20),
            'used' => true,
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        ImpersonationToken::create([
            'token' => hash('sha256', 'recent-imp'),
            'kas_client_id' => $client->id,
            'created_by' => null,
            'expires_at' => now()->addMinutes(5),
            'used' => false,
        ]);

        $this->artisan('workspace:cleanup')
            ->expectsOutputToContain('Workspace cleanup completed.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('external_launch_tokens', ['id' => $oldToken->id]);
        $this->assertDatabaseHas('external_launch_tokens', ['id' => $recentToken->id]);

        $this->assertDatabaseMissing('external_launch_audits', ['id' => $oldAudit->id]);
        $this->assertDatabaseCount('external_launch_audits', 1);

        $this->assertDatabaseMissing('impersonation_tokens', ['id' => $oldImpersonation->id]);
        $this->assertDatabaseCount('impersonation_tokens', 1);
    }
}
