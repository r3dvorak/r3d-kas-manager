<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasClient;
use App\Models\KasMailForward;
use App\Services\Kas\MailSnapshotService;
use Illuminate\Http\Request;

class MailforwardsController extends Controller
{
    public function index(Request $request)
    {
        $kasLogin = $request->query('kas_login');
        $q = $request->query('q');

        $clients = KasClient::orderByRaw('account_comment IS NULL')
            ->orderBy('account_comment', 'asc')
            ->orderBy('account_login', 'asc')
            ->get(['id', 'account_login', 'account_comment']);

        $forwards = KasMailForward::query()
            ->when($kasLogin, fn($qq) => $qq->where('kas_login', strtolower($kasLogin)))
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('mail_forward_address', 'like', '%' . $q . '%')
                      ->orWhere('mail_forward_targets', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('mail_forward_address', 'asc')
            ->paginate(100)
            ->withQueryString();

        return view('admin.mailforwards.index', compact('clients', 'forwards', 'kasLogin', 'q'));
    }

    public function preview(Request $request, MailSnapshotService $svc)
    {
        $kasLogin = (string) $request->query('kas_login', '');
        if ($kasLogin === '') {
            return redirect()->route('admin.mailforwards.index')->with('error', 'Bitte kas_login waehlen.');
        }

        $clients = KasClient::orderByRaw('account_comment IS NULL')
            ->orderBy('account_comment', 'asc')
            ->orderBy('account_login', 'asc')
            ->get(['id', 'account_login', 'account_comment']);

        $diff = $svc->previewMailforwards($kasLogin);

        return view('admin.mailforwards.preview', compact('clients', 'kasLogin', 'diff'));
    }

    public function sync(Request $request, MailSnapshotService $svc)
    {
        $kasLogin = (string) $request->input('kas_login', '');
        if ($kasLogin === '') {
            return back()->with('error', 'Bitte kas_login waehlen.');
        }

        $result = $svc->syncMailforwards($kasLogin);
        return redirect()->route('admin.mailforwards.index', ['kas_login' => $kasLogin])
            ->with('success', 'Sync abgeschlossen. Importiert: ' . ($result['inserted'] ?? 0));
    }
}

