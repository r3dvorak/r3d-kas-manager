<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.7-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * KasClient Management Controller
 */

namespace App\Http\Controllers;

use App\Models\KasClient;
use App\Models\ImpersonationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class KasClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kasClients = KasClient::all();
        return view('kas_clients.index', compact('kasClients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('kas_clients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'api_user'     => 'required|string|max:255',
            'api_password' => 'required|string|max:255',
        ]);

        KasClient::create($request->only(['name', 'api_user', 'api_password']));

        return redirect()->route('kas-clients.index')
            ->with('success', 'Client created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(KasClient $kasClient)
    {
        return view('kas_clients.show', compact('kasClient'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KasClient $kasClient)
    {
        return view('kas_clients.edit', compact('kasClient'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KasClient $kasClient)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'api_user'     => 'required|string|max:255',
            'api_password' => 'required|string|max:255',
        ]);

        $kasClient->update($request->all());

        return redirect()->route('kas-clients.index')
            ->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KasClient $kasClient)
    {
        $kasClient->delete();

        return redirect()->route('kas-clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    /**
     * Handle batch actions for KasClients.
     */
    public function batch(Request $request)
    {
        $action = $request->input('action');
        $ids    = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('kas-clients.index')
                ->with('error', 'Keine Einträge ausgewählt.');
        }

        switch ($action) {
            case 'activate':
                KasClient::whereIn('id', $ids)->update(['active' => 1]);
                $msg = 'Ausgewählte Clients wurden aktiviert.';
                break;

            case 'deactivate':
                KasClient::whereIn('id', $ids)->update(['active' => 0]);
                $msg = 'Ausgewählte Clients wurden deaktiviert.';
                break;

            case 'archive':
                KasClient::whereIn('id', $ids)->update(['archived' => 1]);
                $msg = 'Ausgewählte Clients wurden archiviert.';
                break;

            case 'delete':
                KasClient::whereIn('id', $ids)->delete();
                $msg = 'Ausgewählte Clients wurden gelöscht.';
                break;

            case 'duplicate':
                foreach (KasClient::whereIn('id', $ids)->get() as $client) {
                    $new       = $client->replicate();
                    $new->name = $client->name . ' (Copy)';
                    $new->save();
                }
                $msg = 'Ausgewählte Clients wurden dupliziert.';
                break;

            default:
                $msg = 'Unbekannte Aktion.';
        }

        return redirect()->route('kas-clients.index')->with('success', $msg);
    }

     /**
     * Create impersonation token and redirect (admin only)
     */
    public function createImpersonationToken(KasClient $kasClient)
    {
        dd([
            'guard' => auth()->guard()->getName(),
            'user'  => auth()->user(),
            'can_impersonate' => Gate::allows('impersonate'),
        ]);
        
        $user = auth()->user();

        if (! ($user->role === 'admin' || $user->is_admin)) {
            abort(403, 'Unauthorized');
        }

        $token = Str::random(40);

        ImpersonationToken::create([
            'kas_client_id' => $kasClient->id,
            'token'         => $token,
            'expires_at'    => now()->addMinutes(5),
        ]);

        return redirect()->away(url("/impersonate/{$token}"));

    }

    /**
     * Consume impersonation token and log in as kas_client
     */
    public function consumeImpersonationToken(string $token)
    {
        $impersonation = ImpersonationToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $kasClient = $impersonation->kasClient;

        // one-time token, delete it
        $impersonation->delete();

        Auth::guard('kas_client')->login($kasClient);

        return redirect()->route('client.dashboard')
            ->with('success', 'Eingeloggt als ' . $kasClient->name);
    }
}
