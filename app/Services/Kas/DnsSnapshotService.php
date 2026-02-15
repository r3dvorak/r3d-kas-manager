<?php

namespace App\Services\Kas;

use App\Models\KasClient;
use App\Models\KasDnsRecord;
use App\Models\KasDomain;
use App\Services\Recipes\KasGateway;
use Illuminate\Support\Facades\DB;

class DnsSnapshotService
{
    public function __construct(private KasGateway $kas) {}

    /**
     * Preview DNS diffs by domain.
     *
     * @return array{domains:list<array{domain:string,local:int,remote:int,to_add:int,to_remove:int}>,total_local:int,total_remote:int}
     */
    public function previewDns(string $kasLogin, int $clientId): array
    {
        $kasLogin = strtolower(trim($kasLogin));
        $domains = KasDomain::where('kas_client_id', $clientId)->whereNull('deleted_at')->orderBy('domain_full', 'asc')->get(['id', 'domain_full']);

        $rows = [];
        $totalLocal = 0;
        $totalRemote = 0;

        foreach ($domains as $d) {
            $domainFull = strtolower((string) $d->domain_full);
            if ($domainFull === '') continue;

            $local = $this->localFingerprints((int) $d->id);
            $remote = $this->remoteFingerprints($kasLogin, $domainFull);

            $toAdd = array_diff_key($remote, $local);
            $toRemove = array_diff_key($local, $remote);

            $rows[] = [
                'domain' => $domainFull,
                'local' => count($local),
                'remote' => count($remote),
                'to_add' => count($toAdd),
                'to_remove' => count($toRemove),
            ];

            $totalLocal += count($local);
            $totalRemote += count($remote);
        }

        return ['domains' => $rows, 'total_local' => $totalLocal, 'total_remote' => $totalRemote];
    }

    /**
     * Sync DNS records for all active domains of the client.
     *
     * @return array{domains:int,records:int}
     */
    public function syncDns(string $kasLogin, int $clientId): array
    {
        $kasLogin = strtolower(trim($kasLogin));
        $client = KasClient::find($clientId);
        if (!$client) return ['domains' => 0, 'records' => 0];

        $domains = KasDomain::where('kas_client_id', $clientId)->whereNull('deleted_at')->orderBy('domain_full', 'asc')->get(['id', 'domain_full']);

        $syncedDomains = 0;
        $inserted = 0;
        $now = now();

        foreach ($domains as $d) {
            $domainFull = strtolower((string) $d->domain_full);
            if ($domainFull === '') continue;

            $records = $this->fetchDnsRecords($kasLogin, $domainFull);

            // Replace snapshot per domain id.
            KasDnsRecord::where('domain_id', (int) $d->id)->delete();

            foreach ($records as $r) {
                KasDnsRecord::create([
                    'kas_login' => $kasLogin,
                    'domain_id' => (int) $d->id,
                    'record_zone' => (string) ($r['record_zone'] ?? $domainFull . '.'),
                    'record_name' => (string) ($r['record_name'] ?? ''),
                    'record_type' => (string) ($r['record_type'] ?? ''),
                    'record_data' => (string) ($r['record_data'] ?? ''),
                    'record_aux' => (int) ($r['record_aux'] ?? 0),
                    'record_id_kas' => (string) ($r['record_id'] ?? $r['record_id_kas'] ?? ''),
                    'record_changeable' => (string) ($r['record_changeable'] ?? 'Y'),
                    'record_deleteable' => (string) ($r['record_deleteable'] ?? 'Y'),
                    'data_json' => json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $inserted++;
            }

            $syncedDomains++;
            // Conservative pacing to avoid flood protection.
            usleep(400 * 1000);
        }

        return ['domains' => $syncedDomains, 'records' => $inserted];
    }

    /** @return array<string,bool> */
    private function localFingerprints(int $domainId): array
    {
        $out = [];
        $rows = DB::table('kas_dns_records')->where('domain_id', $domainId)->get([
            'record_zone','record_type','record_name','record_data','record_aux',
        ]);
        foreach ($rows as $r) {
            $fp = $this->fingerprint((string) $r->record_zone, (string) $r->record_type, (string) $r->record_name, (string) $r->record_data, (string) $r->record_aux);
            $out[$fp] = true;
        }
        return $out;
    }

    /** @return array<string,bool> */
    private function remoteFingerprints(string $kasLogin, string $domainFull): array
    {
        $out = [];
        foreach ($this->fetchDnsRecords($kasLogin, $domainFull) as $r) {
            $fp = $this->fingerprint(
                (string) ($r['record_zone'] ?? $domainFull . '.'),
                (string) ($r['record_type'] ?? ''),
                (string) ($r['record_name'] ?? ''),
                (string) ($r['record_data'] ?? ''),
                (string) ($r['record_aux'] ?? 0)
            );
            $out[$fp] = true;
        }
        return $out;
    }

    private function fingerprint(string $zone, string $type, string $name, string $data, string $aux): string
    {
        $zone = strtolower(trim($zone));
        $zone = rtrim($zone, '.');
        $type = strtoupper(trim($type));
        $name = strtolower(trim($name));
        $data = strtolower(trim($data));
        $aux = trim($aux);
        return sha1($zone . '|' . $type . '|' . $name . '|' . $data . '|' . $aux);
    }

    /** @return list<array<string,mixed>> */
    private function fetchDnsRecords(string $kasLogin, string $domainFull): array
    {
        $zoneHost = $domainFull . '.';

        $records = $this->fetchDnsRecordsForNs($kasLogin, $zoneHost, 'ns5.kasserver.com');
        if (empty($records)) {
            $records = $this->fetchDnsRecordsForNs($kasLogin, $zoneHost, 'ns6.kasserver.com');
        }

        return $records;
    }

    /** @return list<array<string,mixed>> */
    private function fetchDnsRecordsForNs(string $kasLogin, string $zoneHost, string $ns): array
    {
        $resp = $this->kas->callForLogin($kasLogin, 'get_dns_settings', [
            'zone_host' => $zoneHost,
            'nameserver' => $ns,
        ]);

        if (!($resp['success'] ?? false)) return [];

        $raw = $resp['raw'] ?? $resp['Response'] ?? $resp;
        $info = $raw['Response']['ReturnInfo'] ?? null;

        if (is_array($info)) {
            // If it's a single record object disguised as associative array, normalize.
            $isList = array_is_list($info);
            return $isList ? $info : [$info];
        }

        return [];
    }
}

