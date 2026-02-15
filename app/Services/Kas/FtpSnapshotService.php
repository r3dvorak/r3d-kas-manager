<?php

namespace App\Services\Kas;

use App\Models\KasClient;
use App\Models\KasFtpUser;
use App\Services\Recipes\KasGateway;
use Illuminate\Support\Facades\DB;

class FtpSnapshotService
{
    public function __construct(private KasGateway $kas) {}

    /**
     * @return array{remote:array<string,bool>,local:array<string,bool>,to_add:list<string>,to_remove:list<string>}
     */
    public function previewFtpusers(string $kasLogin): array
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
    public function syncFtpusers(string $kasLogin): array
    {
        $kasLogin = strtolower(trim($kasLogin));
        $client = KasClient::where('account_login', $kasLogin)->first();
        $clientId = $client?->id;

        $items = $this->fetchFtpusersRaw($kasLogin);

        DB::table('kas_ftp_users')->where('kas_login', $kasLogin)->delete();

        $now = now();
        $inserted = 0;

        foreach ($items as $u) {
            $login = strtolower(trim((string) ($u['ftp_login'] ?? $u['ftp_user'] ?? $u['login'] ?? '')));
            if ($login === '') continue;

            KasFtpUser::create([
                'kas_login' => $kasLogin,
                'client_id' => $clientId,
                'ftp_login' => $login,
                'ftp_path' => $u['ftp_path'] ?? $u['path'] ?? null,
                'ftp_comment' => $u['ftp_comment'] ?? $u['comment'] ?? null,
                'perm_read' => $this->yn($u['ftp_perm_read'] ?? $u['perm_read'] ?? 'N'),
                'perm_write' => $this->yn($u['ftp_perm_write'] ?? $u['perm_write'] ?? 'N'),
                'perm_list' => $this->yn($u['ftp_perm_list'] ?? $u['perm_list'] ?? 'N'),
                'status' => 'active',
                'data_json' => $u,
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
        foreach ($this->fetchFtpusersRaw($kasLogin) as $u) {
            $login = strtolower(trim((string) ($u['ftp_login'] ?? $u['ftp_user'] ?? $u['login'] ?? '')));
            if ($login !== '') $out[$login] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return array<string,bool> */
    private function localLogins(string $kasLogin): array
    {
        $out = [];
        $rows = DB::table('kas_ftp_users')->where('kas_login', $kasLogin)->pluck('ftp_login')->all();
        foreach ($rows as $r) {
            $r = strtolower(trim((string) $r));
            if ($r !== '') $out[$r] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function fetchFtpusersRaw(string $kasLogin): array
    {
        $resp = $this->kas->callForLogin($kasLogin, 'get_ftpusers', []);
        if (!($resp['success'] ?? false)) return [];

        $raw = $resp['Response'] ?? $resp['raw'] ?? $resp;
        $items = $this->collectArraysWithKey($raw, 'ftp_login');
        if (empty($items)) $items = $this->collectArraysWithKey($raw, 'ftp_user');
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

    private function yn(mixed $v): string
    {
        $v = strtoupper(trim((string) $v));
        if ($v === 'Y' || $v === 'J' || $v === '1' || $v === 'TRUE') return 'Y';
        if ($v === 'N' || $v === '0' || $v === 'FALSE') return 'N';
        return 'N';
    }
}

