<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.1.0-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license     GNU General Public License version 2 or later
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
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
            'name' => 'required|string|max:255',
            'api_user' => 'required|string|max:255',
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
            'name' => 'required|string|max:255',
            'api_user' => 'required|string|max:255',
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
}
