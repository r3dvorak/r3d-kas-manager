<?php

namespace App\Services\Kas;

use App\Models\KasClient;
use App\Models\KasDatabase;
use App\Services\Recipes\KasGateway;
use Illuminate\Support\Facades\DB;

class DatabaseSnapshotService
{
    public function __construct(private KasGateway $kas) {}

    /**
     * @return array{remote:array<string,bool>,local:array<string,bool>,to_add:list<string>,to_remove:list<string>}
     */
    public function previewDatabases(string $kasLogin): array
    {
        $kasLogin = strtolower(trim($kasLogin));

        $remote = $this->remoteLogins($kasLogin);
        $local = $this->localLogins($kasLogin);

        $toAdd = array_values(array_diff(array_keys($remote), array_keys($local)));
        sort($toAdd);
        $toRemove = array_values(array_diff(array_keys($local), array_keys($remote)));
        sort($toRemove);

        return [
            'remote' => $remote,
            'local' => $local,
            'to_add' => $toAdd,
            'to_remove' => $toRemove,
        ];
    }

    /**
     * Replace snapshot for one kas login.
     *
     * @return array{inserted:int}
     */
    public function syncDatabases(string $kasLogin): array
    {
        $kasLogin = strtolower(trim($kasLogin));
        $client = KasClient::where('account_login', $kasLogin)->first();
        $clientId = $client?->id;

        $items = $this->fetchDatabasesRaw($kasLogin);

        DB::table('kas_databases')->where('kas_login', $kasLogin)->delete();

        $now = now();
        $inserted = 0;

        foreach ($items as $db) {
            $login = strtolower(trim((string) ($db['database_login'] ?? $db['db_login'] ?? $db['login'] ?? '')));
            if ($login === '') continue;

            KasDatabase::create([
                'kas_login' => $kasLogin,
                'client_id' => $clientId,
                'database_login' => $login,
                'database_comment' => (string) ($db['database_comment'] ?? $db['comment'] ?? ''),
                'database_allowed_hosts' => is_array($db['database_allowed_hosts'] ?? null)
                    ? implode(',', $db['database_allowed_hosts'])
                    : (string) ($db['database_allowed_hosts'] ?? ''),
                'status' => 'active',
                'data_json' => $db,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        return ['inserted' => $inserted];
    }

    /** @return array<string,bool> */
    private function remoteLogins(string $kasLogin): array
    {
        $out = [];
        foreach ($this->fetchDatabasesRaw($kasLogin) as $db) {
            $login = strtolower(trim((string) ($db['database_login'] ?? $db['db_login'] ?? $db['login'] ?? '')));
            if ($login !== '') $out[$login] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return array<string,bool> */
    private function localLogins(string $kasLogin): array
    {
        $out = [];
        $rows = DB::table('kas_databases')->where('kas_login', $kasLogin)->pluck('database_login')->all();
        foreach ($rows as $r) {
            $r = strtolower(trim((string) $r));
            if ($r !== '') $out[$r] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function fetchDatabasesRaw(string $kasLogin): array
    {
        $resp = $this->kas->callForLogin($kasLogin, 'get_databases', []);
        if (!($resp['success'] ?? false)) return [];

        $raw = $resp['Response'] ?? $resp['raw'] ?? $resp;
        $items = $this->collectArraysWithKey($raw, 'database_login');
        if (empty($items)) $items = $this->collectArraysWithKey($raw, 'db_login');
        if (empty($items)) $items = $this->collectArraysWithKey($raw, 'login');
        return $items;
    }

    /** @return list<array<string,mixed>> */
    private function collectArraysWithKey(mixed $data, string $key): array
    {
        $out = [];
        if (is_array($data)) {
            if (array_key_exists($key, $data)) $out[] = $data;
            foreach ($data as $v) {
                if (is_array($v)) {
                    if (array_key_exists($key, $v)) $out[] = $v;
                    else $out = array_merge($out, $this->collectArraysWithKey($v, $key));
                }
            }
        }
        return $out;
    }
}

