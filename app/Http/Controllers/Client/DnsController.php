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

use App\Http\Controllers\Controller;
use App\Models\KasDnsRecord;
use App\Models\KasDomain;
use App\Services\Kas\DnsSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DnsController extends Controller
{
    public function index(Request $request)
    {
        $client = Auth::guard('kas_client')->user();
        $q = (string) $request->query('q', '');
        $domain = (string) $request->query('domain', '');

        $domainOptions = KasDomain::where('kas_client_id', $client->id)
            ->whereNull('deleted_at')
            ->orderBy('domain_full', 'asc')
            ->pluck('domain_full')
            ->all();

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

    public function preview(DnsSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $kasLogin = (string) ($client?->account_login ?? '');

        $diff = $svc->previewDns($kasLogin, (int) $client->id);
        return view('client.dns.preview', compact('diff'));
    }

    public function sync(DnsSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $kasLogin = (string) ($client?->account_login ?? '');

        $res = $svc->syncDns($kasLogin, (int) $client->id);
        return redirect()->route('client.dns.index')->with('success', "Sync abgeschlossen. Domains: {$res['domains']}, Records: {$res['records']}.");
    }
}
