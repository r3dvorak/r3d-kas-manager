<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasDomain;
use App\Services\Kas\SslSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SslController extends Controller
{
    public function index(Request $request)
    {
        $client = Auth::guard('kas_client')->user();
        $q = (string) $request->query('q', '');

        $domains = KasDomain::query()
            ->where('kas_client_id', $client->id)
            ->whereNull('deleted_at')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('domain_full', 'like', '%' . $q . '%')
                      ->orWhere('domain_path', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('domain_full', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('client.ssl.index', compact('domains', 'q'));
    }

    public function preview(SslSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $diff = $svc->previewSsl((string) $client->account_login, (int) $client->id);
        return view('client.ssl.preview', compact('diff'));
    }

    public function sync(SslSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $svc->syncSsl((string) $client->account_login, (int) $client->id);
        return redirect()->route('client.ssl.index')->with('success', 'Sync abgeschlossen.');
    }
}

