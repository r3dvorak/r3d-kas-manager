<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.8.0-alpha
 * @date      2025-09-29
 * @license   MIT License
 * 
 * routes/web.php
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasClientController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocuController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Auth\UnifiedLoginController;

// === Login Auswahlseite ===
Route::get('/login', function () {
    return view('auth.login_select');
})->name('login.select');

// === Admin Login ===
Route::get('/login/admin', [UnifiedLoginController::class, 'showLoginForm'])
    ->middleware(['web','useguard:web'])
    ->name('login.admin');
Route::post('/login/admin', [UnifiedLoginController::class, 'login'])
    ->middleware(['web','useguard:web'])
    ->name('login.admin.submit');

// === Client Login ===
Route::get('/login/client', [UnifiedLoginController::class, 'showLoginForm'])
    ->middleware(['web','useguard:kas_client'])
    ->name('login.client');
Route::post('/login/client', [UnifiedLoginController::class, 'login'])
    ->middleware(['web','useguard:kas_client'])
    ->name('login.client.submit');

// === Logout (für beide) ===
Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout');

// === Admin routes (web guard) ===
Route::middleware(['web','useguard:web','auth:web'])->group(function () {
    Route::get('/', fn() => view('dashboard'))->name('dashboard');
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::resource('kas-clients', KasClientController::class);
    Route::post('kas-clients/batch', [KasClientController::class, 'batch'])->name('kas-clients.batch');
    Route::resource('users', UserController::class);
    Route::post('users/batch', [UserController::class, 'batch'])->name('users.batch');
    Route::get('/docs', [DocuController::class, 'index'])->name('docs');
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');
    Route::get('kas-clients/{kasClient}/impersonate', [KasClientController::class, 'createImpersonationToken'])
        ->middleware('can:impersonate')
        ->name('kas-clients.impersonate.generate');
});

// === Client routes (kas_client guard) ===
Route::prefix('client')->name('client.')->middleware(['web','useguard:kas_client','auth:kas_client'])->group(function () {
    Route::get('/dashboard', fn() => view('client.dashboard'))->name('dashboard');
    Route::get('/domains', [App\Http\Controllers\Client\DomainController::class, 'index'])->name('domains.index');
    Route::get('/mailboxes', [App\Http\Controllers\Client\MailboxController::class, 'index'])->name('mailboxes.index');
    Route::get('/dns', [App\Http\Controllers\Client\DnsController::class, 'index'])->name('dns.index');
    Route::get('/recipes', [App\Http\Controllers\Client\RecipeController::class, 'index'])->name('recipes.index');
});

// === Impersonation public endpoints ===
Route::get('impersonate/{token}', [KasClientController::class, 'consumeImpersonationToken'])
    ->name('kas-clients.impersonate.consume');
Route::post('kas-clients/impersonate/leave', [KasClientController::class, 'leaveImpersonation'])
    ->name('kas-clients.impersonate.leave');
