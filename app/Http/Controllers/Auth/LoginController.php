<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.0-alpha
 * @date      2025-09-24
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * Custom Login Controller (Login/Domain + Passwort)
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $request->input('login');
        $password   = $request->input('password');

        // 1. Prüfen ob direkt ein API-User existiert
        $user = User::where('login', $loginInput)->first();

        // 2. Falls nicht: prüfen ob Eingabe eine Domain ist
        if (! $user) {
            $kasClient = KasClient::whereJsonContains('domains', $loginInput)->first();
            if ($kasClient) {
                $user = User::where('kas_client_id', $kasClient->id)->first();
            }
        }

        // 3. Authentifizieren
        if ($user && Auth::attempt(['login' => $user->login, 'password' => $password])) {
            $request->session()->regenerate();
            return redirect()->intended('kas-clients');
        }

        return back()->withErrors([
            'login' => 'Ungültige Zugangsdaten (Login oder Domain).',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
