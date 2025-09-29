<?php
/**
 * R3D KAS Manager – Web Routes (Login + Public)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.9.2-alpha
 * @date      2025-09-29
 * @license   MIT License
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UnifiedLoginController;
use App\Http\Controllers\KasClientController;

// === Login Auswahlseite ===
Route::get('/login', [UnifiedLoginController::class, 'selectLogin'])->name('login');

// === Admin Login ===
Route::get('/login/admin', [UnifiedLoginController::class, 'showAdminLoginForm'])->name('login.admin');
Route::post('/login/admin', [UnifiedLoginController::class, 'loginAdmin'])->name('login.admin.submit');

// === Client Login ===
Route::get('/login/client', [UnifiedLoginController::class, 'showClientLoginForm'])->name('login.client');
Route::post('/login/client', [UnifiedLoginController::class, 'loginClient'])->name('login.client.submit');

// === Logout ===
Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout');

// === Impersonation public endpoints ===
Route::get('impersonate/{token}', [KasClientController::class, 'consumeImpersonationToken'])
    ->name('kas-clients.impersonate.consume');

Route::post('kas-clients/impersonate/leave', [KasClientController::class, 'leaveImpersonation'])
    ->name('kas-clients.impersonate.leave');
