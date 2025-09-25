<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.0-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * Custom Login: supports login with API-User or Domain.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\KasClient;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $request->input('login');
        $password   = $request->input('password');

        // 1. Prüfen: gibt es User mit login-Feld?
        $credentials = ['login' => $loginInput, 'password' => $password];
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            return redirect()->intended('/');
        }

        // 2. Prüfen: wurde Domain eingegeben?
        $kasClient = KasClient::where('domain', $loginInput)->first();
        if ($kasClient) {
            $user = User::where('kas_client_id', $kasClient->id)->first();
            if ($user && Auth::attempt(['login' => $user->login, 'password' => $password], $request->boolean('remember'))) {
                return redirect()->intended('/');
            }
        }

        return back()->withErrors(['login' => 'Ungültige Login-Daten.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
