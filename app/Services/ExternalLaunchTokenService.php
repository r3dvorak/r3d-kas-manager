<?php
/**
 * R3D KAS Manager - External Launch Token Service
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.19-alpha
 * @date      2026-02-17
 * @license   MIT License
 */

namespace App\Services;

use App\Http\Middleware\ResolveWorkspace;
use App\Models\ExternalLaunchAudit;
use App\Models\ExternalLaunchToken;
use App\Models\KasClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalLaunchTokenService
{
    public const TOOL_WEBMAIL = 'webmail';
    public const TOOL_PMA = 'pma';

    /**
     * @return array{token:string,record:ExternalLaunchToken}
     */
    public function createForClient(KasClient $client, string $tool, Request $request, array $context = []): array
    {
        $tool = strtolower(trim($tool));
        if (!in_array($tool, [self::TOOL_WEBMAIL, self::TOOL_PMA], true)) {
            abort(404);
        }

        $targetUrl = $this->buildTargetUrl($client, $tool, $context);
        $rawToken = Str::random(64);
        $workspace = (string) ($request->attributes->get('workspace') ?? $request->query('w', ''));
        $ttl = (int) env('EXTERNAL_LAUNCH_TOKEN_TTL', 60);
        $expiresAt = CarbonImmutable::now()->addSeconds(max(15, $ttl));

        $record = ExternalLaunchToken::create([
            'token_hash' => hash('sha256', $rawToken),
            'kas_client_id' => (int) $client->id,
            'tool' => $tool,
            'target_url' => $targetUrl,
            'workspace_key' => ResolveWorkspace::isValidWorkspace($workspace) ? $workspace : null,
            'expires_at' => $expiresAt,
        ]);

        $this->audit($record, 'created', $request, $context);
        Log::info('external_launch_token_created', [
            'token_id' => $record->id,
            'tool' => $tool,
            'client_id' => $record->kas_client_id,
            'workspace' => $record->workspace_key,
        ]);

        return [
            'token' => $rawToken,
            'record' => $record,
        ];
    }

    public function consume(string $rawToken, Request $request): ?ExternalLaunchToken
    {
        $record = ExternalLaunchToken::query()
            ->where('token_hash', hash('sha256', $rawToken))
            ->first();

        if (!$record) {
            $this->audit(null, 'invalid_token', $request, ['hash_prefix' => substr(hash('sha256', $rawToken), 0, 12)]);
            Log::warning('external_launch_token_invalid', ['ip' => $request->ip()]);
            return null;
        }

        $requestedWorkspace = strtolower((string) $request->query(ResolveWorkspace::QUERY_KEY, ''));
        if ($record->workspace_key && (!ResolveWorkspace::isValidWorkspace($requestedWorkspace) || $requestedWorkspace !== strtolower((string) $record->workspace_key))) {
            $this->audit($record, 'denied', $request, ['reason' => 'workspace_mismatch']);
            Log::warning('external_launch_token_workspace_mismatch', [
                'token_id' => $record->id,
                'expected_workspace' => $record->workspace_key,
                'received_workspace' => $requestedWorkspace,
            ]);
            return null;
        }

        if ($record->used_at !== null || $record->expires_at->isPast()) {
            $this->audit($record, 'denied', $request, [
                'reason' => $record->used_at !== null ? 'already_used' : 'expired',
            ]);
            Log::warning('external_launch_token_denied', [
                'token_id' => $record->id,
                'reason' => $record->used_at !== null ? 'already_used' : 'expired',
            ]);
            return null;
        }

        $record->forceFill([
            'used_at' => now(),
            'used_ip' => $request->ip(),
            'used_user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ])->save();

        $this->audit($record, 'consumed', $request);
        Log::info('external_launch_token_consumed', [
            'token_id' => $record->id,
            'tool' => $record->tool,
            'client_id' => $record->kas_client_id,
        ]);

        return $record;
    }

    private function buildTargetUrl(KasClient $client, string $tool, array $context = []): string
    {
        $login = strtolower((string) $client->account_login);

        if ($tool === self::TOOL_WEBMAIL) {
            return rtrim((string) env('KAS_WEBMAIL_BASE_URL', 'https://webmail.all-inkl.com/'), '/') . '/';
        }

        $template = (string) env('KAS_PMA_URL_TEMPLATE', 'https://{login}.kasserver.com/mysqladmin/PMA5/index.php');
        $url = str_replace('{login}', $login, $template);

        if (!str_starts_with($url, 'https://')) {
            abort(500, 'Invalid PMA launch URL template');
        }

        if (($context['database_login'] ?? '') !== '') {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 'db=' . rawurlencode((string) $context['database_login']);
        }

        return $url;
    }

    /**
     * @param array<string,mixed> $meta
     */
    private function audit(?ExternalLaunchToken $record, string $event, Request $request, array $meta = []): void
    {
        ExternalLaunchAudit::create([
            'external_launch_token_id' => $record?->id,
            'kas_client_id' => $record?->kas_client_id,
            'event' => $event,
            'tool' => $record?->tool,
            'workspace_key' => $record?->workspace_key,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'meta_json' => $meta === [] ? null : $meta,
        ]);
    }
}
