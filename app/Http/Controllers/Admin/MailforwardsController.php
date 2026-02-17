<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminMailforwardRequest;
use App\Http\Requests\UpdateAdminMailforwardRequest;
use App\Models\KasClient;
use App\Models\KasDomain;
use App\Models\KasMailForward;
use App\Services\Kas\MailSnapshotService;
use Illuminate\Http\Request;

class MailforwardsController extends Controller
{
    private function clientsList()
    {
        return KasClient::orderByRaw('account_comment IS NULL')
            ->orderBy('account_comment', 'asc')
            ->orderBy('account_login', 'asc')
            ->get(['id', 'account_login', 'account_comment']);
    }

    private function resolveDomainId(int $clientId, string $address): ?int
    {
        $parts = explode('@', strtolower(trim($address)));
        $domain = count($parts) === 2 ? $parts[1] : '';
        if ($domain === '') {
            return null;
        }

        return KasDomain::where('kas_client_id', $clientId)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(domain_full) = ?', [$domain])
            ->value('id');
    }

    public function index(Request $request)
    {
        $kasLogin = $request->query('kas_login');
        $q = $request->query('q');

        $clients = $this->clientsList();

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

    public function create()
    {
        $clients = $this->clientsList();
        return view('admin.mailforwards.create', compact('clients'));
    }

    public function store(StoreAdminMailforwardRequest $request)
    {
        $validated = $request->validated();
        $kasLogin = strtolower(trim((string) $validated['kas_login']));
        $client = KasClient::where('account_login', $kasLogin)->firstOrFail();

        $domainId = $this->resolveDomainId((int) $client->id, (string) $validated['mail_forward_address']);

        KasMailForward::create([
            'kas_login' => $kasLogin,
            'mail_forward_address' => strtolower(trim((string) $validated['mail_forward_address'])),
            'mail_forward_targets' => trim((string) $validated['mail_forward_targets']),
            'mail_forward_comment' => (string) ($validated['mail_forward_comment'] ?? ''),
            'mail_forward_spamfilter' => (string) ($validated['mail_forward_spamfilter'] ?? ''),
            'status' => (string) $validated['status'],
            'in_progress' => (bool) ($validated['in_progress'] ?? false),
            'domain_id' => $domainId,
            'client_id' => (int) $client->id,
            'data_json' => [
                'source' => 'admin-manual-ui',
                'updated_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()->route('admin.mailforwards.index', ['kas_login' => $kasLogin])->with('success', 'Weiterleitung angelegt.');
    }

    public function edit(KasMailForward $forward)
    {
        $clients = $this->clientsList();
        return view('admin.mailforwards.edit', compact('clients', 'forward'));
    }

    public function update(UpdateAdminMailforwardRequest $request, KasMailForward $forward)
    {
        $validated = $request->validated();
        $kasLogin = strtolower(trim((string) $validated['kas_login']));
        $client = KasClient::where('account_login', $kasLogin)->firstOrFail();
        $domainId = $this->resolveDomainId((int) $client->id, (string) $validated['mail_forward_address']);

        $forward->update([
            'kas_login' => $kasLogin,
            'mail_forward_address' => strtolower(trim((string) $validated['mail_forward_address'])),
            'mail_forward_targets' => trim((string) $validated['mail_forward_targets']),
            'mail_forward_comment' => (string) ($validated['mail_forward_comment'] ?? ''),
            'mail_forward_spamfilter' => (string) ($validated['mail_forward_spamfilter'] ?? ''),
            'status' => (string) $validated['status'],
            'in_progress' => (bool) ($validated['in_progress'] ?? false),
            'domain_id' => $domainId,
            'client_id' => (int) $client->id,
            'data_json' => [
                'source' => 'admin-manual-ui',
                'updated_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()->route('admin.mailforwards.index', ['kas_login' => $kasLogin])->with('success', 'Weiterleitung aktualisiert.');
    }

    public function destroy(KasMailForward $forward)
    {
        $kasLogin = (string) $forward->kas_login;
        $forward->delete();
        return redirect()->route('admin.mailforwards.index', ['kas_login' => $kasLogin])->with('success', 'Weiterleitung geloescht.');
    }

    public function preview(Request $request, MailSnapshotService $svc)
    {
        $kasLogin = (string) $request->query('kas_login', '');
        if ($kasLogin === '') {
            return redirect()->route('admin.mailforwards.index')->with('error', 'Bitte kas_login waehlen.');
        }

        $clients = $this->clientsList();

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
