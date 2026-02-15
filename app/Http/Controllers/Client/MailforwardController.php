<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasClient;
use App\Models\KasMailForward;
use App\Services\Kas\MailSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MailforwardController extends Controller
{
    public function index(Request $request)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $q = $request->query('q');
        $domain = (string) $request->query('domain', '');

        $client = KasClient::where('account_login', strtolower($kasLogin))->first();
        $domainOptions = $client?->domains()->orderBy('domain_full', 'asc')->pluck('domain_full')->all() ?? [];

        $base = KasMailForward::query()
            ->where('kas_login', strtolower($kasLogin))
            ->when($domain !== '', function ($qq) use ($domain) {
                $domain = strtolower(trim($domain));
                $qq->where(function ($w) use ($domain) {
                    $w->whereHas('domainModel', fn($d) => $d->where('domain_full', $domain))
                      ->orWhere('mail_forward_address', 'like', '%@' . $domain);
                });
            })
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('mail_forward_address', 'like', '%' . $q . '%')
                      ->orWhere('mail_forward_targets', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('mail_forward_address', 'asc');

        $domainTotals = (clone $base)
            ->reorder()
            ->selectRaw("lower(substring_index(mail_forward_address,'@',-1)) as domain, count(*) as cnt")
            ->whereNotNull('mail_forward_address')
            ->where('mail_forward_address', 'like', '%@%')
            ->groupBy('domain')
            ->pluck('cnt', 'domain')
            ->all();

        $forwards = $base->paginate(25)->withQueryString();

        return view('client.mailforwards.index', compact('forwards', 'q', 'domain', 'domainOptions', 'domainTotals', 'kasLogin', 'client'));
    }

    public function preview(MailSnapshotService $svc)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $diff = $svc->previewMailforwards($kasLogin);
        return view('client.mailforwards.preview', compact('diff', 'kasLogin'));
    }

    public function sync(MailSnapshotService $svc)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $svc->syncMailforwards($kasLogin);
        return redirect()->route('client.mailforwards.index')->with('success', 'Sync abgeschlossen.');
    }
}
