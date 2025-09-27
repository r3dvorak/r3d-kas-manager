<?php
/**
 * R3D KAS Manager – Unified Login Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.7.2-alpha
 * @date      2025-09-27
 * @license   MIT License
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnifiedLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login'); // dein unified Login-Formular
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $request->input('login');
        $password   = $request->input('password');

        // === 1. Versuch: User Login (Guard: web) ===
        if (Auth::guard('web')->attempt(['login' => $loginInput, 'password' => $password], $request->filled('remember'))
            || Auth::guard('web')->attempt(['email' => $loginInput, 'password' => $password], $request->filled('remember'))) {
            return redirect()->intended(route('dashboard'));
        }

        // === 2. Versuch: KasClient Login per login ===
        if (Auth::guard('kas_client')->attempt(['login' => $loginInput, 'password' => $password], $request->filled('remember'))) {
            return redirect()->intended(route('client.dashboard'));
        }

        // === 3. Versuch: KasClient Login per domain ===
        if (Auth::guard('kas_client')->attempt(['domain' => $loginInput, 'password' => $password], $request->filled('remember'))) {
            return redirect()->intended(route('client.dashboard'));
        }

        // === Fehlerfall ===
        return back()->withErrors([
            'login' => 'Ungültige Zugangsdaten.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        // erst web abmelden
        Auth::guard('web')->logout();
        // dann kas_client abmelden
        Auth::guard('kas_client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
