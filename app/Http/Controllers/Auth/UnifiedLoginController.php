<?php
/**
 * R3D KAS Manager – Unified Login Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.14.0-alpha
 * @date      2025-10-05
 * @license   MIT License
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\KasClient;

class UnifiedLoginController extends Controller
{
    /**
     * Display the unified login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login attempts for both admin and client users.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login    = trim($request->input('login'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // --- 1️⃣ Try Admin Login (by login or email) ---
        if (
            Auth::guard('web')->attempt(['login' => $login, 'password' => $password], $remember) ||
            Auth::guard('web')->attempt(['email' => $login, 'password' => $password], $remember)
        ) {
            return redirect()->intended(route('dashboard'));
        }

        // --- 2️⃣ Try Client Login (by login name) ---
        if (Auth::guard('kas_client')->attempt(['login' => $login, 'password' => $password], $remember)) {
            return redirect()->intended(route('client.dashboard'));
        }

        // --- 3️⃣ Try Client Login by related Domain or Subdomain ---
        $client = KasClient::whereHas('domains', function ($q) use ($login) {
                $q->where('domain_full', $login);
            })
            ->orWhereHas('subdomains', function ($q) use ($login) {
                $q->where('subdomain_full', $login);
            })
            ->first();

        if ($client && Auth::guard('kas_client')->attempt([
            'login'    => $client->account_login,
            'password' => $password,
        ], $remember)) {
            return redirect()->intended(route('client.dashboard'));
        }

        // --- 4️⃣ If all failed ---
        return back()
            ->withErrors(['login' => 'Ungültige Zugangsdaten.'])
            ->onlyInput('login');
    }

    /**
     * Logout for both guards (admin and client).
     */
    public function logout(Request $request)
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if (Auth::guard('kas_client')->check()) {
            Auth::guard('kas_client')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
