<?php
/**
 * R3D KAS Manager – Unified Login Controller
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.10.4-alpha
 * @date      2025-09-30
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
    public function selectLogin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }
        if (Auth::guard('kas_client')->check()) {
            return redirect()->route('client.dashboard');
        }
        return view('auth.login');
    }

    public function showAdminLoginForm()
    {
        // No redirect checks here → avoid loops
        return view('auth.login');
    }

    public function showClientLoginForm()
    {
        // No redirect checks here → avoid loops
        return view('auth.login');
    }

    public function loginAdmin(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        if (
            Auth::guard('web')->attempt(
                ['login' => $request->login, 'password' => $request->password],
                $request->boolean('remember')
            )
            || Auth::guard('web')->attempt(
                ['email' => $request->login, 'password' => $request->password],
                $request->boolean('remember')
            )
        ) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['login' => 'Invalid credentials'])->onlyInput('login');
    }

    public function loginClient(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        if (
            Auth::guard('kas_client')->attempt(
                ['login' => $request->login, 'password' => $request->password],
                $request->boolean('remember')
            )
            || Auth::guard('kas_client')->attempt(
                ['domain' => $request->login, 'password' => $request->password],
                $request->boolean('remember')
            )
        ) {
            $request->session()->regenerate();
            return redirect()->route('client.dashboard');
        }

        return back()->withErrors(['login' => 'Invalid credentials'])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        $wasAdmin  = Auth::guard('web')->check();
        $wasClient = Auth::guard('kas_client')->check();

        if ($wasAdmin) {
            Auth::guard('web')->logout();
        }
        if ($wasClient) {
            Auth::guard('kas_client')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
