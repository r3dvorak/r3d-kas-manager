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
 * app\Http\Controllers\MailboxController.php
 */

namespace App\Http\Controllers\Client;

use App\Http\Requests\StoreClientMailboxRequest;
use App\Http\Requests\UpdateClientMailboxRequest;
use App\Http\Controllers\Controller;
use App\Models\KasClient;
use App\Models\KasDomain;
use App\Models\KasMailAccount;
use App\Services\Kas\MailSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MailboxController extends Controller
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

    private function assertMailboxOwnership(KasMailAccount $mailbox, string $kasLogin): void
    {
        abort_unless(strtolower((string) $mailbox->kas_login) === strtolower($kasLogin), 404);
    }

    public function index(Request $request)
    {
        $kasLogin = (string) ($this->currentClient()?->account_login ?? '');
        $q = $request->query('q');
        $domain = (string) $request->query('domain', '');

        $client = KasClient::where('account_login', strtolower($kasLogin))->first();
        $domainOptions = $this->ownedDomains((int) $client?->id)->pluck('domain_full')->all();

        $base = KasMailAccount::query()
            ->where('kas_login', strtolower($kasLogin))
            ->when($q, fn($qq) => $qq->where('email', 'like', '%' . $q . '%'))
            ->when($domain !== '', fn($qq) => $qq->where('domain', strtolower($domain)))
            ->orderBy('domain', 'asc')
            ->orderBy('email', 'asc');

        $domainTotals = (clone $base)
            ->reorder()
            ->selectRaw('domain, count(*) as cnt')
            ->groupBy('domain')
            ->pluck('cnt', 'domain')
            ->all();

        // Totals across all matching mailboxes (not just the current page).
        $totalUsedKb = (clone $base)
            ->reorder()
            ->where('status', 'active')
            ->get(['data_json'])
            ->sum(function ($row) {
                $json = $row->data_json ?? null;
                $v = is_array($json) ? ($json['used_mailaccount_space'] ?? null) : null;
                return (is_numeric($v) ? (float) $v : 0.0);
            });

        $totalUsedMb = $totalUsedKb > 0 ? round($totalUsedKb / 1024, 2) : 0.0;
        $totalUsedGb = $totalUsedMb > 0 ? round($totalUsedMb / 1024, 2) : 0.0;

        $mailboxes = $base->paginate(25)->withQueryString();

        return view('client.mailboxes.index', compact('mailboxes', 'q', 'domain', 'domainOptions', 'domainTotals', 'kasLogin', 'client', 'totalUsedGb', 'totalUsedMb'));
    }

    public function create()
    {
        $client = $this->currentClient();
        $domains = $this->ownedDomains((int) $client->id)->get(['id', 'domain_full']);
        return view('client.mailboxes.create', compact('domains'));
    }

    public function store(StoreClientMailboxRequest $request)
    {
        $client = $this->currentClient();
        $validated = $request->validated();

        $domain = $this->ownedDomains((int) $client->id)->where('id', (int) $validated['domain_id'])->firstOrFail();
        $localPart = strtolower(trim((string) $validated['local_part']));
        $domainFull = strtolower((string) $domain->domain_full);
        $email = $localPart . '@' . $domainFull;

        $mailbox = KasMailAccount::create([
            'kas_login' => strtolower((string) $client->account_login),
            'mail_login' => trim((string) ($validated['mail_login'] ?? $localPart)),
            'domain' => $domainFull,
            'email' => $email,
            'status' => (string) $validated['status'],
            'domain_id' => (int) $domain->id,
            'client_id' => (int) $client->id,
            'data_json' => [
                'source' => 'manual-ui',
                'mail_spamfilter' => (string) ($validated['spamfilter'] ?? ''),
                'quota_rule' => $validated['quota_mb'] === null ? null : ('max:' . ((float) $validated['quota_mb']) . 'MB'),
                'used_mailaccount_space' => $validated['used_kb'] ?? 0,
                'updated_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()->route('client.mailboxes.index')->with('success', "Postfach {$mailbox->email} gespeichert.");
    }

    public function edit(KasMailAccount $mailbox)
    {
        $client = $this->currentClient();
        $kasLogin = strtolower((string) $client->account_login);
        $this->assertMailboxOwnership($mailbox, $kasLogin);

        $domains = $this->ownedDomains((int) $client->id)->get(['id', 'domain_full']);
        $localPart = strstr((string) $mailbox->email, '@', true);
        if ($localPart === false || $localPart === '') {
            $localPart = (string) $mailbox->mail_login;
        }

        return view('client.mailboxes.edit', compact('mailbox', 'domains', 'localPart'));
    }

    public function update(UpdateClientMailboxRequest $request, KasMailAccount $mailbox)
    {
        $client = $this->currentClient();
        $kasLogin = strtolower((string) $client->account_login);
        $this->assertMailboxOwnership($mailbox, $kasLogin);
        $validated = $request->validated();

        $domain = $this->ownedDomains((int) $client->id)->where('id', (int) $validated['domain_id'])->firstOrFail();
        $localPart = strtolower(trim((string) $validated['local_part']));
        $domainFull = strtolower((string) $domain->domain_full);
        $email = $localPart . '@' . $domainFull;

        $mailbox->update([
            'mail_login' => trim((string) ($validated['mail_login'] ?? $localPart)),
            'domain' => $domainFull,
            'email' => $email,
            'status' => (string) $validated['status'],
            'domain_id' => (int) $domain->id,
            'client_id' => (int) $client->id,
            'data_json' => [
                'source' => 'manual-ui',
                'mail_spamfilter' => (string) ($validated['spamfilter'] ?? ''),
                'quota_rule' => $validated['quota_mb'] === null ? null : ('max:' . ((float) $validated['quota_mb']) . 'MB'),
                'used_mailaccount_space' => $validated['used_kb'] ?? 0,
                'updated_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()->route('client.mailboxes.index')->with('success', 'Postfach aktualisiert.');
    }

    public function destroy(KasMailAccount $mailbox)
    {
        $client = $this->currentClient();
        $kasLogin = strtolower((string) $client->account_login);
        $this->assertMailboxOwnership($mailbox, $kasLogin);
        $mailbox->delete();
        return redirect()->route('client.mailboxes.index')->with('success', 'Postfach geloescht.');
    }

    public function preview(MailSnapshotService $svc)
    {
        $kasLogin = (string) ($this->currentClient()?->account_login ?? '');
        $diff = $svc->previewMailboxes($kasLogin);
        return view('client.mailboxes.preview', compact('diff', 'kasLogin'));
    }

    public function sync(MailSnapshotService $svc)
    {
        $kasLogin = (string) ($this->currentClient()?->account_login ?? '');
        $svc->syncMailboxes($kasLogin);
        return redirect()->route('client.mailboxes.index')->with('success', 'Sync abgeschlossen.');
    }
}
