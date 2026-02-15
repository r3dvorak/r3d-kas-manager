<?php

namespace App\Services\Kas;

use App\Models\KasClient;
use App\Models\KasDomain;
use App\Models\KasSubdomain;
use App\Services\Recipes\KasGateway;

class SubdomainSnapshotService
{
    public function __construct(private KasGateway $kas) {}

    /**
     * @return array{remote:array<string,bool>,local:array<string,bool>,to_add:list<string>,to_remove:list<string>}
     */
    public function previewSubdomains(string $kasLogin, int $clientId): array
    {
        $kasLogin = strtolower(trim($kasLogin));

        $remote = $this->remoteSubdomainNames($kasLogin);
        $local = $this->localSubdomainNames($clientId);

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
     * Upsert subdomains and soft-delete missing for this client.
     *
     * @return array{upserted:int,deleted:int,total_remote:int}
     */
    public function syncSubdomains(string $kasLogin, int $clientId): array
    {
        $kasLogin = strtolower(trim($kasLogin));

        $client = KasClient::find($clientId);
        if (!$client) return ['upserted' => 0, 'deleted' => 0, 'total_remote' => 0];

        $items = $this->fetchSubdomainsRaw($kasLogin);
        $remote = [];
        $upserted = 0;

        $domainMap = KasDomain::where('kas_client_id', $clientId)
            ->whereNull('deleted_at')
            ->get(['id', 'domain_full'])
            ->mapWithKeys(fn($d) => [strtolower((string) $d->domain_full) => (int) $d->id])
            ->all();

        foreach ($items as $s) {
            $full = $this->normalize((string) ($s['subdomain_full'] ?? $s['subdomain'] ?? ''));
            if ($full === '') continue;
            $remote[$full] = true;

            $domainId = $this->guessDomainId($full, $domainMap);
            $name = $this->subdomainLabel($full, array_keys($domainMap));

            $record = [
                'kas_client_id' => $clientId,
                'domain_id' => $domainId,
                'subdomain_name' => $name,
                'subdomain_full' => $full,
                'subdomain_path' => $s['subdomain_path'] ?? $s['subdomain_docroot'] ?? null,
                'php_version' => $s['php_version'] ?? null,
                'redirect_status' => $this->bool($s['redirect_status'] ?? $s['subdomain_redirect_status'] ?? false),
                'redirect_target' => $s['redirect_target'] ?? $s['subdomain_redirect_target'] ?? null,
                'ssl_status' => $this->bool($s['ssl_status'] ?? $s['ssl_certificate_sni_is_active'] ?? false),
                'active' => $this->bool($s['active'] ?? $s['is_active'] ?? true),
                'deleted_at' => null,
            ];

            $existing = KasSubdomain::withTrashed()->where('kas_client_id', $clientId)->where('subdomain_full', $full)->first();
            if ($existing) {
                $existing->fill($record);
                $existing->save();
            } else {
                KasSubdomain::create($record);
            }
            $upserted++;
        }

        $deleted = 0;
        $locals = KasSubdomain::where('kas_client_id', $clientId)->whereNull('deleted_at')->get(['id', 'subdomain_full']);
        foreach ($locals as $l) {
            $f = $this->normalize((string) $l->subdomain_full);
            if ($f === '' || isset($remote[$f])) continue;
            $l->delete();
            $deleted++;
        }

        return ['upserted' => $upserted, 'deleted' => $deleted, 'total_remote' => count($remote)];
    }

    /** @return array<string,bool> */
    private function remoteSubdomainNames(string $kasLogin): array
    {
        $out = [];
        foreach ($this->fetchSubdomainsRaw($kasLogin) as $s) {
            $full = $this->normalize((string) ($s['subdomain_full'] ?? $s['subdomain'] ?? ''));
            if ($full !== '') $out[$full] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return array<string,bool> */
    private function localSubdomainNames(int $clientId): array
    {
        $out = [];
        $rows = KasSubdomain::where('kas_client_id', $clientId)->whereNull('deleted_at')->pluck('subdomain_full')->all();
        foreach ($rows as $r) {
            $f = $this->normalize((string) $r);
            if ($f !== '') $out[$f] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function fetchSubdomainsRaw(string $kasLogin): array
    {
        $resp = $this->kas->callForLogin($kasLogin, 'get_subdomains', []);
        if (!($resp['success'] ?? false)) return [];

        $raw = $resp['Response'] ?? $resp['raw'] ?? $resp;
        $items = $this->collectArraysWithKey($raw, 'subdomain_full');
        if (empty($items)) $items = $this->collectArraysWithKey($raw, 'subdomain');
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

    private function normalize(string $v): string
    {
        $v = strtolower(trim($v));
        return rtrim($v, '.');
    }

    /** @param array<string,int> $domainMap */
    private function guessDomainId(string $subdomainFull, array $domainMap): ?int
    {
        $subdomainFull = $this->normalize($subdomainFull);
        $best = null;
        $bestLen = 0;
        foreach ($domainMap as $domainFull => $id) {
            $domainFull = $this->normalize($domainFull);
            if ($domainFull === '') continue;
            if ($subdomainFull === $domainFull) continue;
            if (str_ends_with($subdomainFull, '.' . $domainFull) && strlen($domainFull) > $bestLen) {
                $best = $id;
                $bestLen = strlen($domainFull);
            }
        }
        return $best;
    }

    /** @param list<string> $domains */
    private function subdomainLabel(string $subdomainFull, array $domains): string
    {
        $subdomainFull = $this->normalize($subdomainFull);
        $bestDomain = '';
        foreach ($domains as $d) {
            $d = $this->normalize((string) $d);
            if ($d !== '' && str_ends_with($subdomainFull, '.' . $d) && strlen($d) > strlen($bestDomain)) {
                $bestDomain = $d;
            }
        }
        if ($bestDomain === '') {
            return explode('.', $subdomainFull, 2)[0] ?? $subdomainFull;
        }

        $label = substr($subdomainFull, 0, -1 - strlen($bestDomain));
        return $label === '' ? $subdomainFull : $label;
    }

    private function bool(mixed $v): bool
    {
        $s = strtoupper(trim((string) $v));
        if ($s === 'Y' || $s === 'J' || $s === '1' || $s === 'TRUE') return true;
        if ($s === 'N' || $s === '0' || $s === 'FALSE') return false;
        return (bool) $v;
    }
}

