<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.3-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * User Management Controller
 */

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'login'    => 'required|string|max:255|unique:users',
            'email'    => 'nullable|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|string|in:admin,user',
        ]);

        // Hash the password before storing
        $validated['password'] = Hash::make($validated['password']);

        // Ensure consistency with your table defaults
        $validated['is_admin'] = ($validated['role'] === 'admin') ? 1 : 0;

        \App\Models\User::create($validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'User erfolgreich angelegt.');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'login'    => 'required|string|max:255|unique:users,login,' . $user->id,
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role'     => 'required|string|in:admin,user',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Benutzer wurde aktualisiert.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Benutzer wurde gelöscht.');
    }

    public function show(\App\Models\User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Batch operations (activate, deactivate, archive, delete, duplicate).
     */
    public function batch(Request $request)
    {
        $action = $request->input('action');
        $ids    = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('users.index')->with('error', 'Keine Benutzer ausgewählt.');
        }

        switch ($action) {
            case 'activate':
                User::whereIn('id', $ids)->update(['active' => 1]);
                $msg = 'Ausgewählte Benutzer wurden aktiviert.';
                break;

            case 'deactivate':
                User::whereIn('id', $ids)->update(['active' => 0]);
                $msg = 'Ausgewählte Benutzer wurden deaktiviert.';
                break;

            case 'archive':
                User::whereIn('id', $ids)->update(['archived' => 1]);
                $msg = 'Ausgewählte Benutzer wurden archiviert.';
                break;

            case 'delete':
                User::whereIn('id', $ids)->delete();
                $msg = 'Ausgewählte Benutzer wurden gelöscht.';
                break;

            case 'duplicate':
                foreach (User::whereIn('id', $ids)->get() as $u) {
                    $new = $u->replicate();
                    $new->name  = $u->name . ' (Copy)';
                    $new->login = $u->login . '_copy';
                    $new->email = $u->email ? 'copy_' . $u->email : null;
                    $new->save();
                }
                $msg = 'Ausgewählte Benutzer wurden dupliziert.';
                break;

            default:
                $msg = 'Unbekannte Aktion.';
        }

        return redirect()->route('users.index')->with('success', $msg);
    }
}
