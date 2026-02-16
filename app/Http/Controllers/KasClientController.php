<?php
/**
 * R3D KAS Manager – KasClient Management Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.14.0-alpha
 * @date      2025-10-05
 * 
 * @license   MIT License
 * @copyright (C) 2025
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreKasClientRequest;
use App\Http\Requests\UpdateKasClientRequest;
use App\Models\KasClient;
use App\Models\KasDomain;
use App\Models\ImpersonationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class KasClientController extends Controller
{
    /** Display a listing of the resource. */
    public function index()
    {
        // Sort by description/comment (ascending) so the list is stable and predictable.
        $kasClients = KasClient::with('domains')
            ->orderByRaw('account_comment IS NULL')
            ->orderBy('account_comment', 'asc')
            ->orderBy('account_login', 'asc')
            ->get();
        return view('kas_clients.index', compact('kasClients'));
    }

    /** Show the form for creating a new resource. */
    public function create()
    {
        return view('kas_clients.create');
    }

    /** Store a newly created resource in storage. */
    public function store(StoreKasClientRequest $request)
    {
        $validated = $request->validated();

        try {
            KasClient::create([
                // NOTE: Secrets (KAS API password / login password) are managed via CSV sync commands.
                'account_comment' => $validated['account_comment'],
                'account_login' => strtolower(trim((string) $validated['account_login'])),
                'account_contact_mail' => $validated['account_contact_mail'] ?? null,
                'server_internal_domain' => $validated['server_internal_domain'] ?? null,
                'server_hostname' => $validated['server_hostname'] ?? null,
                'server_ip' => $validated['server_ip'] ?? null,
                'all_inkl_customer_number' => $validated['all_inkl_customer_number'] ?? null,
                'all_inkl_contract_number' => $validated['all_inkl_contract_number'] ?? null,
            ]);

            return redirect()
                ->route('kas-clients.index')
                ->with('success', 'Client erfolgreich angelegt.');

        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Fehler beim Erstellen: ' . $e->getMessage()]);
        }
    }


    /** Display the specified resource. */
    public function show(KasClient $kasClient)
    {
        $kasClient->load('domains.subdomains');
        return view('kas_clients.show', compact('kasClient'));
    }

    /** Show the form for editing the specified resource. */
    public function edit(KasClient $kasClient)
    {
        return view('kas_clients.edit', compact('kasClient'));
    }

    /** Update the specified resource in storage. */
    public function update(UpdateKasClientRequest $request, KasClient $kasClient)
    {
        $kasClient->update($request->validated());

        return redirect()->route('kas-clients.index')
            ->with('success', 'Clientdaten erfolgreich aktualisiert.');
    }

    /** Remove the specified resource from storage. */
    public function destroy(KasClient $kasClient)
    {
        $kasClient->delete();

        return redirect()->route('kas-clients.index')
            ->with('success', 'Client gelöscht.');
    }

    /** Handle batch actions for KasClients. */
    public function batch(Request $request)
    {
        $action = $request->input('action');
        $ids    = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('kas-clients.index')
                ->with('error', 'Keine Einträge ausgewählt.');
        }

        switch ($action) {
            case 'delete':
                KasClient::whereIn('id', $ids)->delete();
                $msg = 'Ausgewählte Clients wurden gelöscht.';
                break;

            default:
                $msg = 'Unbekannte Aktion.';
        }

        return redirect()->route('kas-clients.index')->with('success', $msg);
    }

    /** Create impersonation token and redirect (admin only). */
    public function createImpersonationToken(KasClient $kasClient)
    {
        if (! Gate::allows('impersonate')) {
            abort(403, 'Unauthorized');
        }

        session(['admin_id' => Auth::id()]);

        $token = ImpersonationToken::generateForClient($kasClient->id, auth()->id());
        $url = route('kas-clients.impersonate.consume', $token->getRawToken());

        return redirect()->away($url);
    }

    /** Consume impersonation token and log in as kas_client. */
    public function consumeImpersonationToken(string $token)
    {
        $impersonation = ImpersonationToken::findByRawToken($token);

        if (! $impersonation || $impersonation->expires_at->isPast()) {
            abort(403, 'Ungültiger oder abgelaufener Token.');
        }

        $kasClient = $impersonation->kasClient;
        $impersonation->update(['used' => true]);

        Auth::guard('kas_client')->login($kasClient);
        session(['impersonate' => true]);

        $label = (string) ($kasClient->account_comment ?: $kasClient->account_login);
        return redirect()->route('client.dashboard')
            ->with('success', 'Eingeloggt als ' . $label);
    }

    /** Leave impersonation and return to admin panel. */
    public function leaveImpersonation()
    {
        Auth::guard('kas_client')->logout();

        if (session()->has('admin_id')) {
            Auth::loginUsingId(session('admin_id'));
        }

        session()->forget(['impersonate', 'admin_id']);

        return redirect()->route('dashboard')
            ->with('success', 'Zurück zum Admin gewechselt.');
    }
}
