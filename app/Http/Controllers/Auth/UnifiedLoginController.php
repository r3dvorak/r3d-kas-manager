<?php
/**
 * R3D KAS Manager – Unified Login Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.8.0-alpha
 * @date      2025-09-29
 * @license   MIT License
 * 
 * app\Http\Controllers\Auth\UnifiedLoginController.php
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnifiedLoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        return view('auth.login'); // dein vorhandenes Login-Formular
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('login', 'password');

        // Entscheiden anhand der Route, ob Admin oder Client
        if ($request->routeIs('login.client.submit')) {
            if (Auth::guard('kas_client')->attempt($credentials, $request->filled('remember'))) {
                $request->session()->regenerate();
                return redirect()->intended(route('client.dashboard'));
            }
        }

        if ($request->routeIs('login.admin.submit')) {
            if (Auth::guard('web')->attempt($credentials, $request->filled('remember'))) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }
        }

        return back()->withErrors([
            'login' => 'Ungültige Zugangsdaten.',
        ]);
    }

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

        return redirect()->route('login.select');
    }
}
