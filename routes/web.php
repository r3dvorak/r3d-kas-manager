<?php
/**
 * R3D KAS Manager – Web Routes
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.10.3-alpha
 * @date      2025-09-30
 * 
 * @license   MIT License
 * @copyright (C) 2025
 * 
 * routes\web.php
 */


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UnifiedLoginController;
use Illuminate\Support\Facades\Auth;

// === Root redirect ===
Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        return redirect()->route('dashboard');
    }
    if (Auth::guard('kas_client')->check()) {
        return redirect()->route('client.dashboard');
    }
    return redirect()->route('login');
})->name('home');

// === Login page (neutral) ===
Route::get('/login', [UnifiedLoginController::class, 'selectLogin'])->name('login');

// === Admin Login ===
Route::middleware('web_admin')->group(function () {
    Route::get('/login/admin', [UnifiedLoginController::class, 'showAdminLoginForm'])->name('login.admin');
    Route::post('/login/admin', [UnifiedLoginController::class, 'loginAdmin'])->name('login.admin.submit');
});

// === Client Login ===
Route::middleware('web_client')->group(function () {
    Route::get('/login/client', [UnifiedLoginController::class, 'showClientLoginForm'])->name('login.client');
    Route::post('/login/client', [UnifiedLoginController::class, 'loginClient'])->name('login.client.submit');
});

// === Logout (neutral) ===
Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout');

// === Impersonation ===
Route::get('impersonate/{token}', [App\Http\Controllers\KasClientController::class, 'consumeImpersonationToken'])
    ->name('kas-clients.impersonate.consume');

Route::post('kas-clients/impersonate/leave', [App\Http\Controllers\KasClientController::class, 'leaveImpersonation'])
    ->name('kas-clients.impersonate.leave');
