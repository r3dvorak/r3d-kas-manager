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
 * app\Http\Controllers\DomainController.php
 */

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasDomain;
use App\Services\Kas\DomainSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $client = Auth::guard('kas_client')->user();
        $q = (string) $request->query('q', '');

        $domains = KasDomain::query()
            ->where('kas_client_id', $client->id)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('domain_full', 'like', '%' . $q . '%')
                      ->orWhere('domain_name', 'like', '%' . $q . '%')
                      ->orWhere('domain_tld', 'like', '%' . $q . '%')
                      ->orWhere('domain_path', 'like', '%' . $q . '%');
                });
            })
            ->withCount('subdomains')
            ->orderBy('domain_full', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('client.domains.index', compact('domains', 'q', 'client'));
    }

    public function preview(DomainSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $kasLogin = (string) ($client?->account_login ?? '');

        $diff = $svc->previewDomains($kasLogin, (int) $client->id);
        return view('client.domains.preview', compact('diff'));
    }

    public function sync(DomainSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $kasLogin = (string) ($client?->account_login ?? '');

        $res = $svc->syncDomains($kasLogin, (int) $client->id);
        return redirect()->route('client.domains.index')->with('success', "Sync abgeschlossen. Upserted: {$res['upserted']}, Deleted: {$res['deleted']}.");
    }
}
