<?php
/**
 * R3D KAS Manager – Unified Login Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.8.0-alpha
 * @date      2025-09-29
 * @license   MIT License
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnifiedLoginController extends Controller
{
    public function selectLogin()
    {
        return view('auth.login_select');
    }

    public function showAdminLoginForm()
    {
        return view('auth.login'); // erkennt anhand routeIs, dass es Admin ist
    }

    public function showClientLoginForm()
    {
        return view('auth.login'); // erkennt anhand routeIs, dass es Client ist
    }

    public function loginAdmin(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::guard('web')->attempt(
            ['login' => $request->login, 'password' => $request->password],
            $request->boolean('remember')
        ) || Auth::guard('web')->attempt(
            ['email' => $request->login, 'password' => $request->password],
            $request->boolean('remember')
        )) {
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['login' => 'Ungültige Zugangsdaten.'])->onlyInput('login');
    }

    public function loginClient(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::guard('kas_client')->attempt(
            ['login' => $request->login, 'password' => $request->password],
            $request->boolean('remember')
        ) || Auth::guard('kas_client')->attempt(
            ['domain' => $request->login, 'password' => $request->password],
            $request->boolean('remember')
        )) {
            return redirect()->intended(route('client.dashboard'));
        }

        return back()->withErrors(['login' => 'Ungültige Zugangsdaten.'])->onlyInput('login');
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

        return redirect()->route('login');
    }
}
