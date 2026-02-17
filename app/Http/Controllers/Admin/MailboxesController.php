<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminMailboxRequest;
use App\Http\Requests\UpdateAdminMailboxRequest;
use App\Models\KasClient;
use App\Models\KasDomain;
use App\Models\KasMailAccount;
use App\Services\Kas\MailSnapshotService;
use Illuminate\Http\Request;

class MailboxesController extends Controller
{
    private function clientsList()
    {
        return KasClient::orderByRaw('account_comment IS NULL')
            ->orderBy('account_comment', 'asc')
            ->orderBy('account_login', 'asc')
            ->get(['id', 'account_login', 'account_comment']);
    }

    private function resolveDomainId(int $clientId, string $domain): ?int
    {
        return KasDomain::where('kas_client_id', $clientId)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(domain_full) = ?', [strtolower($domain)])
            ->value('id');
    }

    public function index(Request $request)
    {
        $kasLogin = $request->query('kas_login');
        $q = $request->query('q');

        $clients = $this->clientsList();

        $mailboxes = KasMailAccount::query()
            ->when($kasLogin, fn($qq) => $qq->where('kas_login', strtolower($kasLogin)))
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('email', 'like', '%' . $q . '%')
                      ->orWhere('mail_login', 'like', '%' . $q . '%')
                      ->orWhere('domain', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('domain', 'asc')
            ->orderBy('email', 'asc')
            ->paginate(100)
            ->withQueryString();

        return view('admin.mailboxes.index', compact('clients', 'mailboxes', 'kasLogin', 'q'));
    }

    public function create()
    {
        $clients = $this->clientsList();
        return view('admin.mailboxes.create', compact('clients'));
    }

    public function store(StoreAdminMailboxRequest $request)
    {
        $validated = $request->validated();
        $kasLogin = strtolower(trim((string) $validated['kas_login']));
        $domain = strtolower(trim((string) $validated['domain']));
        $localPart = strtolower(trim((string) $validated['local_part']));
        $email = $localPart . '@' . $domain;

        $client = KasClient::where('account_login', $kasLogin)->firstOrFail();
        $domainId = $this->resolveDomainId((int) $client->id, $domain);

        KasMailAccount::create([
            'kas_login' => $kasLogin,
            'mail_login' => trim((string) ($validated['mail_login'] ?? $localPart)),
            'domain' => $domain,
            'email' => $email,
            'status' => (string) $validated['status'],
            'domain_id' => $domainId,
            'client_id' => (int) $client->id,
            'data_json' => [
                'source' => 'admin-manual-ui',
                'mail_spamfilter' => (string) ($validated['spamfilter'] ?? ''),
                'quota_rule' => $validated['quota_mb'] === null ? null : ('max:' . ((float) $validated['quota_mb']) . 'MB'),
                'used_mailaccount_space' => $validated['used_kb'] ?? 0,
                'updated_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()->route('admin.mailboxes.index', ['kas_login' => $kasLogin])->with('success', 'Mailbox angelegt.');
    }

    public function edit(KasMailAccount $mailbox)
    {
        $clients = $this->clientsList();
        $localPart = strstr((string) $mailbox->email, '@', true);
        if ($localPart === false || $localPart === '') {
            $localPart = (string) $mailbox->mail_login;
        }

        return view('admin.mailboxes.edit', compact('clients', 'mailbox', 'localPart'));
    }

    public function update(UpdateAdminMailboxRequest $request, KasMailAccount $mailbox)
    {
        $validated = $request->validated();
        $kasLogin = strtolower(trim((string) $validated['kas_login']));
        $domain = strtolower(trim((string) $validated['domain']));
        $localPart = strtolower(trim((string) $validated['local_part']));
        $email = $localPart . '@' . $domain;

        $client = KasClient::where('account_login', $kasLogin)->firstOrFail();
        $domainId = $this->resolveDomainId((int) $client->id, $domain);

        $mailbox->update([
            'kas_login' => $kasLogin,
            'mail_login' => trim((string) ($validated['mail_login'] ?? $localPart)),
            'domain' => $domain,
            'email' => $email,
            'status' => (string) $validated['status'],
            'domain_id' => $domainId,
            'client_id' => (int) $client->id,
            'data_json' => [
                'source' => 'admin-manual-ui',
                'mail_spamfilter' => (string) ($validated['spamfilter'] ?? ''),
                'quota_rule' => $validated['quota_mb'] === null ? null : ('max:' . ((float) $validated['quota_mb']) . 'MB'),
                'used_mailaccount_space' => $validated['used_kb'] ?? 0,
                'updated_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()->route('admin.mailboxes.index', ['kas_login' => $kasLogin])->with('success', 'Mailbox aktualisiert.');
    }

    public function destroy(KasMailAccount $mailbox)
    {
        $kasLogin = (string) $mailbox->kas_login;
        $mailbox->delete();
        return redirect()->route('admin.mailboxes.index', ['kas_login' => $kasLogin])->with('success', 'Mailbox geloescht.');
    }

    public function preview(Request $request, MailSnapshotService $svc)
    {
        $kasLogin = (string) $request->query('kas_login', '');
        if ($kasLogin === '') {
            return redirect()->route('admin.mailboxes.index')->with('error', 'Bitte kas_login waehlen.');
        }

        $clients = $this->clientsList();

        $diff = $svc->previewMailboxes($kasLogin);

        return view('admin.mailboxes.preview', compact('clients', 'kasLogin', 'diff'));
    }

    public function sync(Request $request, MailSnapshotService $svc)
    {
        $kasLogin = (string) $request->input('kas_login', '');
        if ($kasLogin === '') {
            return back()->with('error', 'Bitte kas_login waehlen.');
        }

        $result = $svc->syncMailboxes($kasLogin);
        return redirect()->route('admin.mailboxes.index', ['kas_login' => $kasLogin])
            ->with('success', 'Sync abgeschlossen. Importiert: ' . ($result['inserted'] ?? 0));
    }
}
