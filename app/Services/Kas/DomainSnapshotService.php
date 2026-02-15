<?php

namespace App\Services\Kas;

use App\Models\KasClient;
use App\Models\KasDomain;
use App\Services\Recipes\KasGateway;
use Illuminate\Support\Facades\DB;

class DomainSnapshotService
{
    public function __construct(private KasGateway $kas) {}

    /**
     * @return array{remote:array<string,bool>,local:array<string,bool>,to_add:list<string>,to_remove:list<string>}
     */
    public function previewDomains(string $kasLogin, int $clientId): array
    {
        $kasLogin = strtolower(trim($kasLogin));

        $remote = $this->remoteDomainNames($kasLogin);
        $local  = $this->localDomainNames($clientId);

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
     * Sync domains for a client from KAS API into kas_domains.
     * We upsert by domain_full and soft-delete domains no longer present remotely.
     *
     * @return array{upserted:int,deleted:int,total_remote:int}
     */
    public function syncDomains(string $kasLogin, int $clientId): array
    {
        $kasLogin = strtolower(trim($kasLogin));

        $client = KasClient::find($clientId);
        if (!$client) {
            return ['upserted' => 0, 'deleted' => 0, 'total_remote' => 0];
        }

        $remoteItems = $this->fetchDomainsRaw($kasLogin);
        $remoteNames = [];
        $upserted = 0;

        foreach ($remoteItems as $dom) {
            $domainFull = $this->normalizeDomain((string) ($dom['domain_name'] ?? $dom['domain'] ?? $dom['domain_full'] ?? ''));
            if ($domainFull === '') continue;

            $remoteNames[$domainFull] = true;

            [$short, $tld] = $this->splitDomain($domainFull);

            $record = [
                'kas_client_id' => $client->id,
                'domain_name' => $short,
                'domain_tld' => $tld,
                'domain_full' => $domainFull,
                'domain_path' => $dom['domain_path'] ?? null,
                'domain_redirect_status' => (int) ($dom['domain_redirect_status'] ?? $dom['domain_redirect_status'] ?? 0),
                'dummy_host' => $this->yn($dom['dummy_host'] ?? 'N'),
                'fpse_active' => $this->yn($dom['fpse_active'] ?? 'N'),
                'dkim_selector' => $dom['dkim_selector'] ?? null,
                'statistic_language' => $dom['statistic_language'] ?? null,
                'statistic_version' => $dom['statistic_version'] ?? null,
                'ssl_proxy' => $this->yn($dom['ssl_proxy'] ?? 'N'),
                'ssl_certificate_ip' => $this->yn($dom['ssl_certificate_ip'] ?? 'N'),
                'ssl_certificate_sni' => $this->yn($dom['ssl_certificate_sni'] ?? ($dom['ssl_certificate_sni_is_active'] ?? 'N')),
                'php_version' => $dom['php_version'] ?? null,
                'php_deprecated' => $this->yn($dom['php_deprecated'] ?? 'N'),
                'is_active' => $this->yn($dom['is_active'] ?? 'Y'),
                'in_progress' => $this->yn($dom['in_progress'] ?? 'N'),
                'deleted_at' => null,
            ];

            // Upsert while preserving IDs for dependent tables (subdomains/dns).
            $existing = KasDomain::withTrashed()->where('domain_full', $domainFull)->first();
            if ($existing) {
                $existing->fill($record);
                $existing->save();
            } else {
                KasDomain::create($record);
            }
            $upserted++;
        }

        // Soft-delete local domains for this client that are no longer present remotely.
        $deleted = 0;
        $local = KasDomain::where('kas_client_id', $clientId)->whereNull('deleted_at')->get(['id', 'domain_full']);
        foreach ($local as $d) {
            $df = strtolower((string) $d->domain_full);
            if ($df === '' || isset($remoteNames[$df])) continue;
            $d->delete();
            $deleted++;
        }

        return ['upserted' => $upserted, 'deleted' => $deleted, 'total_remote' => count($remoteNames)];
    }

    /** @return array<string,bool> */
    private function remoteDomainNames(string $kasLogin): array
    {
        $out = [];
        foreach ($this->fetchDomainsRaw($kasLogin) as $dom) {
            $domainFull = $this->normalizeDomain((string) ($dom['domain_name'] ?? $dom['domain'] ?? $dom['domain_full'] ?? ''));
            if ($domainFull !== '') $out[$domainFull] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return array<string,bool> */
    private function localDomainNames(int $clientId): array
    {
        $out = [];
        $rows = DB::table('kas_domains')
            ->where('kas_client_id', $clientId)
            ->whereNull('deleted_at')
            ->pluck('domain_full')
            ->all();

        foreach ($rows as $d) {
            $d = $this->normalizeDomain((string) $d);
            if ($d !== '') $out[$d] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function fetchDomainsRaw(string $kasLogin): array
    {
        $resp = $this->kas->callForLogin($kasLogin, 'get_domains', []);
        if (!($resp['success'] ?? false)) return [];

        $raw = $resp['Response'] ?? $resp['raw'] ?? $resp;
        $items = $this->collectArraysWithKey($raw, 'domain_name');
        if (empty($items)) $items = $this->collectArraysWithKey($raw, 'domain_full');
        if (empty($items)) $items = $this->collectArraysWithKey($raw, 'domain');
        return $items;
    }

    /** @return list<array<string,mixed>> */
    private function collectArraysWithKey(mixed $data, string $key): array
    {
        $out = [];
        if (is_array($data)) {
            if (array_key_exists($key, $data)) {
                $out[] = $data;
            }
            foreach ($data as $v) {
                if (is_array($v)) {
                    if (array_key_exists($key, $v)) {
                        $out[] = $v;
                    } else {
                        $out = array_merge($out, $this->collectArraysWithKey($v, $key));
                    }
                }
            }
        }
        return $out;
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = rtrim($domain, '.');
        return $domain;
    }

    /** @return array{0:string,1:string|null} */
    private function splitDomain(string $domainFull): array
    {
        $parts = explode('.', $domainFull);
        $tld = array_pop($parts);
        $short = implode('.', $parts);
        return [$short, $tld ?: null];
    }

    private function yn(mixed $v): string
    {
        $v = strtoupper(trim((string) $v));
        if ($v === 'Y' || $v === 'J' || $v === '1' || $v === 'TRUE') return 'Y';
        if ($v === 'N' || $v === '0' || $v === 'FALSE') return 'N';
        // Default: keep as-is if it's already a single char, otherwise 'N'
        if (strlen($v) === 1) return $v;
        return 'N';
    }
}

