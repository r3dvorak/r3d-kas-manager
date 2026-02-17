<?php
/**
 * R3D KAS Manager – Unified Login Controller
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.28.8-alpha
 * @date      2025-10-05
 * @license   MIT License
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\KasClient;

class UnifiedLoginController extends Controller
{
    /**
     * Display the unified login form.
     */
    public function showLoginForm()
    {
        $workspace = $this->workspaceQuery($request = request());

        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard', $workspace ? ['w' => $workspace] : []);
        }

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
        $workspace = $this->workspaceQuery($request);

        // --- 1️⃣ Try Admin Login (by login or email) ---
        if (
            Auth::guard('web')->attempt(['login' => $login, 'password' => $password], $remember) ||
            Auth::guard('web')->attempt(['email' => $login, 'password' => $password], $remember)
        ) {
            return redirect()->route('dashboard', $workspace ? ['w' => $workspace] : []);
        }

        // --- 2️⃣ Try Client Login (by login name) ---
        if ($this->attemptKasClientLoginByAccount(strtolower($login), $password, $remember)) {
            return redirect()->route('client.dashboard', $workspace ? ['w' => $workspace] : []);
        }

        // --- 3️⃣ Try Client Login by related Domain or Subdomain ---
        $client = KasClient::whereHas('domains', function ($q) use ($login) {
                $q->where('domain_full', $login);
            })
            ->orWhereHas('subdomains', function ($q) use ($login) {
                $q->where('subdomain_full', $login);
            })
            ->first();

        if ($client && $this->attemptKasClientLoginByAccount((string) $client->account_login, $password, $remember)) {
            return redirect()->route('client.dashboard', $workspace ? ['w' => $workspace] : []);
        }

        // --- 4️⃣ If all failed ---
        return back()
            ->withErrors(['login' => 'Ungültige Zugangsdaten.'])
            ->onlyInput('login');
    }

    private function workspaceQuery(Request $request): ?string
    {
        $workspace = (string) ($request->query('w') ?? $request->attributes->get('workspace') ?? '');
        return ResolveWorkspace::isValidWorkspace($workspace) ? $workspace : null;
    }

    /**
     * Try kas_client login with two strategies:
     * 1) default Laravel hash in `password`
     * 2) fallback against decrypted `account_password` (KAS password), then refresh hash
     */
    private function attemptKasClientLoginByAccount(string $accountLogin, string $plainPassword, bool $remember): bool
    {
        if ($accountLogin === '') {
            return false;
        }

        if (Auth::guard('kas_client')->attempt(['account_login' => $accountLogin, 'password' => $plainPassword], $remember)) {
            return true;
        }

        $client = KasClient::where('account_login', $accountLogin)->first();
        if (!$client) {
            return false;
        }

        // Fallback: allow login with current KAS password stored for API access.
        // This keeps domain/login authentication working even if Laravel hash is stale.
        $apiPassword = (string) ($client->account_password ?? '');
        if ($apiPassword !== '' && hash_equals($apiPassword, $plainPassword)) {
            // Refresh local login hash to keep future logins on standard guard checks.
            $client->password = Hash::make($plainPassword);
            $client->save();

            Auth::guard('kas_client')->login($client, $remember);
            return true;
        }

        return false;
    }

    /**
     * Logout for both guards (admin and client).
     */
    public function logout(Request $request)
    {
        $logoutAllWorkspaces = $request->input('scope') === 'all';

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if (Auth::guard('kas_client')->check()) {
            Auth::guard('kas_client')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($logoutAllWorkspaces) {
            $this->forgetAllWorkspaceSessionCookies($request);
        }

        return redirect()->route('login');
    }

    private function forgetAllWorkspaceSessionCookies(Request $request): void
    {
        $base = (string) env(
            'SESSION_COOKIE_WORKSPACE',
            Str::slug((string) env('APP_NAME', 'laravel'), '_') . '_workspace_session'
        );
        $prefix = $base . '_';
        $path = (string) config('session.path', '/');
        $domain = config('session.domain');

        foreach (array_keys($request->cookies->all()) as $cookieName) {
            if (!str_starts_with($cookieName, $prefix)) {
                continue;
            }

            Cookie::queue(cookie()->forget($cookieName, $path, $domain));
        }
    }
}
