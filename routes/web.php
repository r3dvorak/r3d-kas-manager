<?php
/**
 * R3D KAS Manager – Web Routes
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.10.5-alpha
 * @date      2025-09-30
 * 
 * @license   MIT License
 * @copyright (C) 2025
 * 
 * routes\web.php
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\UnifiedLoginController;

// === Root route ===
Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        return redirect()->route('dashboard');
    }
    if (Auth::guard('kas_client')->check()) {
        return redirect()->route('client.dashboard');
    }
    return redirect()->route('login');
})->name('home');

// === Login GET routes (no guard/session middleware!) ===
Route::get('/login', [UnifiedLoginController::class, 'selectLogin'])->name('login');
Route::get('/login/admin', [UnifiedLoginController::class, 'showAdminLoginForm'])->name('login.admin');
Route::get('/login/client', [UnifiedLoginController::class, 'showClientLoginForm'])->name('login.client');

// === Login POST routes (with proper session groups) ===
Route::post('/login/admin', [UnifiedLoginController::class, 'loginAdmin'])
    ->middleware('web_admin')
    ->name('login.admin.submit');

Route::post('/login/client', [UnifiedLoginController::class, 'loginClient'])
    ->middleware('web_client')
    ->name('login.client.submit');

// === Logout ===
Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout');

// === Impersonation ===
Route::get('impersonate/{token}', [App\Http\Controllers\KasClientController::class, 'consumeImpersonationToken'])
    ->name('kas-clients.impersonate.consume');

Route::post('kas-clients/impersonate/leave', [App\Http\Controllers\KasClientController::class, 'leaveImpersonation'])
    ->name('kas-clients.impersonate.leave');
