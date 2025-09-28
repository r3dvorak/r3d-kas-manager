<?php
/**
 * R3D KAS Manager – Unified Login Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.7.5-alpha
 * @date      2025-09-28
 * @license   MIT License
 * 
 * app\Http\Controllers\Auth\UnifiedLoginController.php
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\KasClient;

class UnifiedLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login'); // unified Login-Formular
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $request->input('login');
        $password   = $request->input('password');
        $remember   = $request->boolean('remember');

        // === 1. Versuch: Admin Login (Guard: web) ===
        if (Auth::guard('web')->attempt(['login' => $loginInput, 'password' => $password], $remember)
            || Auth::guard('web')->attempt(['email' => $loginInput, 'password' => $password], $remember)) {
            return redirect()->intended(route('dashboard'));
        }

        // === 2. Versuch: KasClient Login (login, email, domain) ===
        $client = KasClient::where('login', $loginInput)
            ->orWhere('email', $loginInput)
            ->orWhere('domain', $loginInput)
            ->first();

        if ($client && Hash::check($password, $client->password)) {
            Auth::guard('kas_client')->login($client, $remember);
            return redirect()->intended(route('client.dashboard'));
        }

        // === Fehlerfall ===
        return back()->withErrors([
            'login' => 'Ungültige Zugangsdaten.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        // Nur den gerade aktiven Guard abmelden
        if (Auth::guard('kas_client')->check()) {
            Auth::guard('kas_client')->logout();
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
