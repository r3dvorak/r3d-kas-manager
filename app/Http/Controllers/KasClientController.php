<?php
/**
 * R3D KAS Manager – KasClient Management Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.28.19-alpha
 * @date      2025-10-05
 * 
 * @license   MIT License
 * @copyright (C) 2025
 */

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveWorkspace;
use App\Http\Requests\StoreKasClientRequest;
use App\Http\Requests\UpdateKasClientRequest;
use App\Models\KasClient;
use App\Models\KasDomain;
use App\Models\ImpersonationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
        $sourceMenuItems = $validated['client_menu_items'] ?? KasClient::clientNavKeys();
        if (!array_key_exists('client_menu_items', $validated) && $request->input('client_menu_items_present') === '1') {
            $sourceMenuItems = [];
        }
        $menuItems = array_values(array_intersect(
            KasClient::clientNavKeys(),
            array_map('strval', (array) $sourceMenuItems)
        ));

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
                'preferred_locale' => $validated['preferred_locale'] ?? 'de',
                'client_menu_items' => $menuItems,
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
        $validated = $request->validated();
        $menuItems = array_values(array_intersect(
            KasClient::clientNavKeys(),
            array_map('strval', (array) ($validated['client_menu_items'] ?? []))
        ));

        if (!array_key_exists('client_menu_items', $validated) && $request->input('client_menu_items_present') === '1') {
            $menuItems = [];
        }

        $kasClient->update([
            'account_comment' => $validated['account_comment'],
            'account_contact_mail' => $validated['account_contact_mail'] ?? null,
            'server_internal_domain' => $validated['server_internal_domain'] ?? null,
            'server_hostname' => $validated['server_hostname'] ?? null,
            'server_ip' => $validated['server_ip'] ?? null,
            'all_inkl_customer_number' => $validated['all_inkl_customer_number'] ?? null,
            'all_inkl_contract_number' => $validated['all_inkl_contract_number'] ?? null,
            'preferred_locale' => $validated['preferred_locale'] ?? 'de',
            'client_menu_items' => $menuItems,
        ]);

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

        $token = ImpersonationToken::generateForClient($kasClient->id, auth()->id());
        $workspace = ResolveWorkspace::generateWorkspaceKey();
        Log::info('impersonation_token_created', [
            'admin_id' => Auth::id(),
            'client_id' => $kasClient->id,
            'workspace' => $workspace,
        ]);
        $url = route('kas-clients.impersonate.consume', [
            'token' => $token->getRawToken(),
            ResolveWorkspace::QUERY_KEY => $workspace,
        ]);

        return redirect()->away($url);
    }

    /** Consume impersonation token and log in as kas_client. */
    public function consumeImpersonationToken(string $token)
    {
        $impersonation = ImpersonationToken::findByRawToken($token);

        if (! $impersonation || $impersonation->expires_at->isPast()) {
            Log::warning('impersonation_consume_denied', ['reason' => 'invalid_or_expired']);
            abort(403, 'Ungültiger oder abgelaufener Token.');
        }

        $kasClient = $impersonation->kasClient;
        $impersonation->update(['used' => true]);

        Auth::guard('web')->logout();
        Auth::guard('kas_client')->login($kasClient);
        session([
            'impersonate' => true,
            'impersonate_admin_id' => $impersonation->created_by,
        ]);
        Log::info('impersonation_consume_success', [
            'admin_id' => $impersonation->created_by,
            'client_id' => $kasClient->id,
            'workspace' => request()->attributes->get('workspace'),
        ]);

        $label = (string) ($kasClient->account_comment ?: $kasClient->account_login);
        return redirect()->route('client.dashboard')
            ->with('success', 'Eingeloggt als ' . $label);
    }

    /** Leave impersonation and return to admin panel. */
    public function leaveImpersonation()
    {
        Auth::guard('kas_client')->logout();

        if (session()->has('impersonate_admin_id')) {
            Auth::guard('web')->loginUsingId((int) session('impersonate_admin_id'));
        }

        session()->forget(['impersonate', 'impersonate_admin_id']);
        Log::info('impersonation_leave', [
            'workspace' => request()->attributes->get('workspace'),
            'ip' => request()->ip(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Zurück zum Admin gewechselt.');
    }
}
