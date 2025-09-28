<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.7.6-alpha
 * @date      2025-09-28
 * 
 * @copyright (C) 2025 Richard Dvořák
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

// === Unified Login ===
Route::get('/login', [UnifiedLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UnifiedLoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout');

// === Admin routes (web guard) ===
Route::middleware(['web','useguard:web','auth:web'])->group(function () {

    // Dashboard
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    // SEARCH
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Accounts (KAS Clients)
    Route::resource('kas-clients', KasClientController::class);
    Route::post('kas-clients/batch', [KasClientController::class, 'batch'])->name('kas-clients.batch');

    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/batch', [UserController::class, 'batch'])->name('users.batch');

    // Doku
    Route::get('/docs', [DocuController::class, 'index'])->name('docs');

    // Stats
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');

    // Impersonation helper — generates token and redirects to /impersonate/{rawToken}
    Route::get('kas-clients/{kasClient}/impersonate', [KasClientController::class, 'createImpersonationToken'])
        ->middleware('can:impersonate') // zusätzlich prüfen wir admin im Controller
        ->name('kas-clients.impersonate.generate');
});

// === Client routes (kas_client guard) ===
Route::prefix('client')->name('client.')->middleware(['web','useguard:kas_client','auth:kas_client'])->group(function () {

    Route::get('/dashboard', function () {
        return view('client.dashboard');
    })->name('dashboard');

    // Domains
    Route::get('/domains', [App\Http\Controllers\Client\DomainController::class, 'index'])
        ->name('domains.index');

    // Mailkonten
    Route::get('/mailboxes', [App\Http\Controllers\Client\MailboxController::class, 'index'])
        ->name('mailboxes.index');

    // DNS
    Route::get('/dns', [App\Http\Controllers\Client\DnsController::class, 'index'])
        ->name('dns.index');

    // Rezepte
    Route::get('/recipes', [App\Http\Controllers\Client\RecipeController::class, 'index'])
        ->name('recipes.index');
});

// === Impersonation public endpoints ===
Route::get('impersonate/{token}', [KasClientController::class, 'consumeImpersonationToken'])
    ->name('kas-clients.impersonate.consume');

Route::post('kas-clients/impersonate/leave', [KasClientController::class, 'leaveImpersonation'])
    ->name('kas-clients.impersonate.leave');
