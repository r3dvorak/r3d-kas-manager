<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.5-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * KasClient Management Controller
 */

namespace App\Http\Controllers;

use App\Models\KasClient;
use Illuminate\Http\Request;

class KasClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = KasClient::all();
        return view('kas_clients.index', compact('clients'));
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
            'name'        => 'required|string|max:255',
            'api_user'    => 'required|string|max:255',
            'api_password'=> 'required|string|max:255',
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
            'name'        => 'required|string|max:255',
            'api_user'    => 'required|string|max:255',
            'api_password'=> 'required|string|max:255',
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
}
