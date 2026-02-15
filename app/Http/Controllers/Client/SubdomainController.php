<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasDomain;
use App\Models\KasSubdomain;
use App\Services\Kas\SubdomainSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubdomainController extends Controller
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

        $subdomains = KasSubdomain::query()
            ->where('kas_client_id', $client->id)
            ->whereNull('deleted_at')
            ->when($domain !== '', function ($qq) use ($domain) {
                $domain = strtolower(trim($domain));
                $qq->where('subdomain_full', 'like', '%.' . $domain);
            })
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('subdomain_full', 'like', '%' . $q . '%')
                      ->orWhere('subdomain_path', 'like', '%' . $q . '%')
                      ->orWhere('php_version', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('subdomain_full', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('client.subdomains.index', compact('subdomains', 'q', 'domain', 'domainOptions'));
    }

    public function preview(SubdomainSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $diff = $svc->previewSubdomains((string) $client->account_login, (int) $client->id);
        return view('client.subdomains.preview', compact('diff'));
    }

    public function sync(SubdomainSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $res = $svc->syncSubdomains((string) $client->account_login, (int) $client->id);
        return redirect()->route('client.subdomains.index')->with('success', "Sync abgeschlossen. Upserted: {$res['upserted']}, Deleted: {$res['deleted']}.");
    }
}

