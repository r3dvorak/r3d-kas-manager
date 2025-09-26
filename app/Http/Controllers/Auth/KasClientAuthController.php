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
 * Authentication controller for KAS Clients
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KasClientAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('client.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::guard('kas_client')->attempt([
            'login' => $credentials['login'],
            'api_password' => $credentials['password'],
        ])) {
            $request->session()->regenerate();
            return redirect()->route('client.dashboard');
        }

        return back()->withErrors([
            'login' => 'Die Zugangsdaten sind ungültig.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::guard('kas_client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('kas-client.login');
    }
}
