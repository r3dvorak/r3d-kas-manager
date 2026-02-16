<?php

namespace App\Services\Kas;

use App\Models\KasClient;
use App\Models\KasSpaceReport;
use App\Services\Recipes\KasGateway;

class StatisticsSnapshotService
{
    public function __construct(private KasGateway $kas) {}

    /**
     * @return array{remote_used_kb:int|null,local_used_kb:int|null,remote_max_kb:int|null,local_max_kb:int|null}
     */
    public function previewSpace(string $kasLogin, int $clientId): array
    {
        $kasLogin = strtolower(trim($kasLogin));

        $remote = $this->fetchSpace($kasLogin);
        $local = KasSpaceReport::where('client_id', $clientId)->orderByDesc('measured_at')->first();

        return [
            'remote_used_kb' => $remote['used_kb'],
            'remote_max_kb' => $remote['max_kb'],
            'local_used_kb' => $local?->used_kb,
            'local_max_kb' => $local?->max_kb,
        ];
    }

    /**
     * @return array{saved:bool,measured_at:string|null,used_kb:int|null,max_kb:int|null}
     */
    public function syncSpace(string $kasLogin, int $clientId): array
    {
        $kasLogin = strtolower(trim($kasLogin));
        $client = KasClient::find($clientId);
        if (!$client) return ['saved' => false, 'measured_at' => null, 'used_kb' => null, 'max_kb' => null];

        $space = $this->fetchSpace($kasLogin);
        $measuredAt = now();

        KasSpaceReport::create([
            'kas_login' => $kasLogin,
            'client_id' => $clientId,
            'measured_at' => $measuredAt,
            'used_kb' => $space['used_kb'],
            'max_kb' => $space['max_kb'],
            'data_json' => $space['raw'],
        ]);

        // Also refresh the quick-used value on the client record for dashboards.
        if (is_int($space['used_kb'])) {
            $client->used_account_space = round($space['used_kb'] / 1024, 2); // KB -> MB
            $client->save();
        }

        return [
            'saved' => true,
            'measured_at' => $measuredAt->format('Y-m-d H:i:s'),
            'used_kb' => $space['used_kb'],
            'max_kb' => $space['max_kb'],
        ];
    }

    /**
     * @return array{used_kb:int|null,max_kb:int|null,raw:array}
     */
    private function fetchSpace(string $kasLogin): array
    {
        $resp = $this->kas->callForLogin($kasLogin, 'get_space', [
            'show_details' => 'N',
        ]);

        if (!($resp['success'] ?? false)) {
            return ['used_kb' => null, 'max_kb' => null, 'raw' => []];
        }

        $raw = $resp['raw'] ?? $resp['Response'] ?? $resp;
        $info = $raw['Response']['ReturnInfo'] ?? $raw['ReturnInfo'] ?? $raw['Response'] ?? [];
        // KAS often returns ReturnInfo as a list with a single element at index 0.
        if (is_array($info) && array_key_exists(0, $info) && is_array($info[0])) {
            $info = $info[0];
        }

        $used = null;
        $max = null;
        if (is_array($info)) {
            // get_space (KAS) uses these keys (values are in KB).
            $used = $this->toInt(
                $info['used_webspace']
                    ?? $info['space_used']
                    ?? $info['space_used_kb']
                    ?? $info['used_kb']
                    ?? $info['used']
                    ?? null
            );
            $max = $this->toInt(
                $info['max_webspace']
                    ?? $info['space_max']
                    ?? $info['space_max_kb']
                    ?? $info['max_kb']
                    ?? $info['max']
                    ?? null
            );
        }

        return ['used_kb' => $used, 'max_kb' => $max, 'raw' => is_array($raw) ? $raw : []];
    }

    private function toInt(mixed $v): ?int
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (int) round((float) $v);
        return null;
    }
}
