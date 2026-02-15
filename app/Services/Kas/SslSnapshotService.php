<?php

namespace App\Services\Kas;

use App\Models\KasDomain;
use App\Services\Recipes\KasGateway;
use Illuminate\Support\Arr;

class SslSnapshotService
{
    public function __construct(private KasGateway $kas, private DomainSnapshotService $domains) {}

    /**
     * Preview SSL flag diffs between remote get_domains and local kas_domains.
     *
     * @return array{changed:list<array{domain:string,local:string,remote:string}>,total:int}
     */
    public function previewSsl(string $kasLogin, int $clientId): array
    {
        $kasLogin = strtolower(trim($kasLogin));

        $remote = $this->remoteSslMap($kasLogin);
        $local = KasDomain::where('kas_client_id', $clientId)
            ->whereNull('deleted_at')
            ->get(['domain_full', 'ssl_proxy', 'ssl_certificate_ip', 'ssl_certificate_sni'])
            ->mapWithKeys(function ($d) {
                $k = strtolower((string) $d->domain_full);
                $v = $this->sslLabel((string) $d->ssl_proxy, (string) $d->ssl_certificate_ip, (string) $d->ssl_certificate_sni);
                return [$k => $v];
            })
            ->all();

        $changed = [];
        foreach ($remote as $domain => $remoteLabel) {
            $localLabel = $local[$domain] ?? '—';
            if ($localLabel !== $remoteLabel) {
                $changed[] = ['domain' => $domain, 'local' => $localLabel, 'remote' => $remoteLabel];
            }
        }

        usort($changed, fn($a, $b) => strcmp($a['domain'], $b['domain']));
        return ['changed' => $changed, 'total' => count($changed)];
    }

    /**
     * Sync SSL flags by syncing domains.
     */
    public function syncSsl(string $kasLogin, int $clientId): array
    {
        return $this->domains->syncDomains($kasLogin, $clientId);
    }

    /** @return array<string,string> domain => label */
    private function remoteSslMap(string $kasLogin): array
    {
        $resp = $this->kas->callForLogin($kasLogin, 'get_domains', []);
        if (!($resp['success'] ?? false)) return [];

        $raw = $resp['Response'] ?? $resp['raw'] ?? $resp;
        $items = $this->collectArraysWithKey($raw, 'domain_name');
        if (empty($items)) $items = $this->collectArraysWithKey($raw, 'domain_full');

        $out = [];
        foreach ($items as $dom) {
            $domain = strtolower(trim((string) ($dom['domain_name'] ?? $dom['domain_full'] ?? '')));
            $domain = rtrim($domain, '.');
            if ($domain === '') continue;

            $proxy = (string) ($dom['ssl_proxy'] ?? 'N');
            $ip = (string) ($dom['ssl_certificate_ip'] ?? 'N');
            $sni = (string) ($dom['ssl_certificate_sni'] ?? ($dom['ssl_certificate_sni_is_active'] ?? 'N'));
            $out[$domain] = $this->sslLabel($proxy, $ip, $sni);
        }

        ksort($out);
        return $out;
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

    private function sslLabel(string $proxy, string $ip, string $sni): string
    {
        $on = fn(string $v) => strtoupper(trim($v)) === 'Y' || strtoupper(trim($v)) === 'J' || trim($v) === '1';
        if ($on($proxy) || $on($ip) || $on($sni)) return 'aktiv';
        return '—';
    }
}

