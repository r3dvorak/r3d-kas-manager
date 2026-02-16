<?php
/**
 * R3D KAS Manager – Domain Controller (Client)
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.7.0-alpha
 * @date      2025-09-27
 * @license   MIT License
 * 
 * app\Http\Controllers\DnsController.php
 */

namespace App\Http\Controllers\Client;

use App\Http\Requests\StoreClientDnsRecordRequest;
use App\Http\Requests\UpdateClientDnsRecordRequest;
use App\Http\Controllers\Controller;
use App\Models\KasClient;
use App\Models\KasDnsRecord;
use App\Models\KasDomain;
use App\Services\Kas\DnsSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DnsController extends Controller
{
    private function currentClient(): KasClient
    {
        return Auth::guard('kas_client')->user();
    }

    private function ownedDomains(int $clientId)
    {
        return KasDomain::query()
            ->where('kas_client_id', $clientId)
            ->whereNull('deleted_at')
            ->orderBy('domain_full', 'asc');
    }

    private function assertRecordOwnership(KasDnsRecord $record, int $clientId): void
    {
        $owned = KasDomain::where('id', $record->domain_id)
            ->where('kas_client_id', $clientId)
            ->whereNull('deleted_at')
            ->exists();

        abort_unless($owned, 404);
    }

    public function index(Request $request)
    {
        $client = $this->currentClient();
        $q = (string) $request->query('q', '');
        $domain = (string) $request->query('domain', '');

        $domainOptions = $this->ownedDomains((int) $client->id)->pluck('domain_full')->all();

        $records = KasDnsRecord::query()
            ->whereHas('domain', function ($qq) use ($client, $domain) {
                $qq->where('kas_client_id', $client->id)->whereNull('deleted_at');
                if ($domain !== '') {
                    $qq->where('domain_full', strtolower(trim($domain)));
                }
            })
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('record_name', 'like', '%' . $q . '%')
                      ->orWhere('record_type', 'like', '%' . $q . '%')
                      ->orWhere('record_data', 'like', '%' . $q . '%')
                      ->orWhere('record_zone', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('record_zone', 'asc')
            ->orderBy('record_type', 'asc')
            ->orderBy('record_name', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('client.dns.index', compact('records', 'q', 'domain', 'domainOptions'));
    }

    public function create()
    {
        $client = $this->currentClient();
        $domains = $this->ownedDomains((int) $client->id)->get(['id', 'domain_full']);
        return view('client.dns.create', compact('domains'));
    }

    public function store(StoreClientDnsRecordRequest $request)
    {
        $client = $this->currentClient();
        $validated = $request->validated();

        $domain = $this->ownedDomains((int) $client->id)->where('id', (int) $validated['domain_id'])->firstOrFail();

        $record = KasDnsRecord::create([
            'kas_login' => strtolower((string) $client->account_login),
            'domain_id' => (int) $domain->id,
            'record_zone' => (string) $domain->domain_full,
            'record_name' => strtolower(trim((string) ($validated['record_name'] ?? ''))),
            'record_type' => strtoupper((string) $validated['record_type']),
            'record_data' => trim((string) $validated['record_data']),
            'record_aux' => (int) ($validated['record_aux'] ?? 0),
            'record_changeable' => 'Y',
            'record_deleteable' => 'Y',
            'data_json' => json_encode([
                'source' => 'manual-ui',
                'updated_at' => now()->toIso8601String(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return redirect()->route('client.dns.index')->with('success', "DNS-Eintrag {$record->record_type} gespeichert.");
    }

    public function edit(KasDnsRecord $dnsRecord)
    {
        $client = $this->currentClient();
        $this->assertRecordOwnership($dnsRecord, (int) $client->id);
        $domains = $this->ownedDomains((int) $client->id)->get(['id', 'domain_full']);
        return view('client.dns.edit', compact('dnsRecord', 'domains'));
    }

    public function update(UpdateClientDnsRecordRequest $request, KasDnsRecord $dnsRecord)
    {
        $client = $this->currentClient();
        $this->assertRecordOwnership($dnsRecord, (int) $client->id);
        $validated = $request->validated();

        $domain = $this->ownedDomains((int) $client->id)->where('id', (int) $validated['domain_id'])->firstOrFail();

        $dnsRecord->update([
            'domain_id' => (int) $domain->id,
            'record_zone' => (string) $domain->domain_full,
            'record_name' => strtolower(trim((string) ($validated['record_name'] ?? ''))),
            'record_type' => strtoupper((string) $validated['record_type']),
            'record_data' => trim((string) $validated['record_data']),
            'record_aux' => (int) ($validated['record_aux'] ?? 0),
            'data_json' => json_encode([
                'source' => 'manual-ui',
                'updated_at' => now()->toIso8601String(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return redirect()->route('client.dns.index')->with('success', 'DNS-Eintrag aktualisiert.');
    }

    public function destroy(KasDnsRecord $dnsRecord)
    {
        $client = $this->currentClient();
        $this->assertRecordOwnership($dnsRecord, (int) $client->id);
        $dnsRecord->delete();
        return redirect()->route('client.dns.index')->with('success', 'DNS-Eintrag geloescht.');
    }

    public function preview(DnsSnapshotService $svc)
    {
        $client = $this->currentClient();
        $kasLogin = (string) ($client?->account_login ?? '');

        $diff = $svc->previewDns($kasLogin, (int) $client->id);
        return view('client.dns.preview', compact('diff'));
    }

    public function sync(DnsSnapshotService $svc)
    {
        $client = $this->currentClient();
        $kasLogin = (string) ($client?->account_login ?? '');

        $res = $svc->syncDns($kasLogin, (int) $client->id);
        return redirect()->route('client.dns.index')->with('success', "Sync abgeschlossen. Domains: {$res['domains']}, Records: {$res['records']}.");
    }
}
