<?php
/**
 * R3D KAS Manager - Workspace Cleanup Command
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.19-alpha
 * @date      2026-02-17
 * @license   MIT License
 */

namespace App\Console\Commands;

use App\Models\ExternalLaunchAudit;
use App\Models\ExternalLaunchToken;
use App\Models\ImpersonationToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WorkspaceCleanup extends Command
{
    protected $signature = 'workspace:cleanup {--dry-run : Show counts only without deleting}';
    protected $description = 'Cleanup stale workspace-related security artifacts (launch/impersonation tokens, audits)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tokenRetentionDays = max(1, (int) env('EXTERNAL_LAUNCH_TOKEN_RETENTION_DAYS', 7));
        $auditRetentionDays = max(1, (int) env('EXTERNAL_LAUNCH_AUDIT_RETENTION_DAYS', 30));
        $impersonationRetentionDays = max(1, (int) env('IMPERSONATION_TOKEN_RETENTION_DAYS', 7));

        $tokenQuery = ExternalLaunchToken::query()->where(function ($q) use ($tokenRetentionDays) {
            $q->whereNotNull('used_at')->where('used_at', '<', now()->subDays($tokenRetentionDays))
                ->orWhere('expires_at', '<', now()->subDays($tokenRetentionDays));
        });

        $auditQuery = ExternalLaunchAudit::query()->where('created_at', '<', now()->subDays($auditRetentionDays));

        $impersonationQuery = ImpersonationToken::query()->where(function ($q) use ($impersonationRetentionDays) {
            $q->where('used', true)->where('updated_at', '<', now()->subDays($impersonationRetentionDays))
                ->orWhere('expires_at', '<', now()->subDays($impersonationRetentionDays));
        });

        $counts = [
            'external_launch_tokens' => (clone $tokenQuery)->count(),
            'external_launch_audits' => (clone $auditQuery)->count(),
            'impersonation_tokens' => (clone $impersonationQuery)->count(),
        ];

        if ($dryRun) {
            $this->info('Dry-run cleanup preview:');
            foreach ($counts as $table => $count) {
                $this->line("- {$table}: {$count}");
            }
            return self::SUCCESS;
        }

        $deleted = [
            'external_launch_tokens' => $tokenQuery->delete(),
            'external_launch_audits' => $auditQuery->delete(),
            'impersonation_tokens' => $impersonationQuery->delete(),
        ];

        $this->info('Workspace cleanup completed.');
        foreach ($deleted as $table => $count) {
            $this->line("- {$table}: {$count}");
        }

        Log::info('workspace_cleanup_completed', [
            'deleted' => $deleted,
            'retention_days' => [
                'external_launch_tokens' => $tokenRetentionDays,
                'external_launch_audits' => $auditRetentionDays,
                'impersonation_tokens' => $impersonationRetentionDays,
            ],
        ]);

        return self::SUCCESS;
    }
}
