<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.9-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * routes\web.php
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasClientController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocuController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\KasClientAuthController;

// Dashboard
Route::get('/', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// SEARCH
Route::get('/search', [SearchController::class, 'index'])
    ->middleware('auth')
    ->name('search');

// Accounts (KAS Clients)
Route::resource('kas-clients', KasClientController::class)
    ->middleware('auth');
Route::post('kas-clients/batch', [KasClientController::class, 'batch'])
    ->middleware('auth')
    ->name('kas-clients.batch');

// User Management
Route::resource('users', UserController::class)
    ->middleware('auth');
Route::post('users/batch', [UserController::class, 'batch'])
    ->middleware('auth')
    ->name('users.batch');

// Doku (statische Seite)
Route::get('/docs', [DocuController::class, 'index'])
    ->middleware('auth')
    ->name('docs');

// Stats
Route::get('/stats', [StatsController::class, 'index'])
    ->middleware('auth')
    ->name('stats');

    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    // Als KAS Client einloggen (neues Fenster)
    // Route::get('/kas-clients/{kasClient}/login', [KasClientController::class, 'clientLogin'])->name('kas-clients.login');

    // KAS Client Auth
    Route::get('/client/login', [KasClientAuthController::class, 'showLoginForm'])->name('kas-client.login');
    Route::post('/client/login', [KasClientAuthController::class, 'login'])->name('kas-client.login.submit');
    Route::post('/client/logout', [KasClientAuthController::class, 'logout'])->name('kas-client.logout');

    // Client Dashboard (geschützt)
    Route::middleware('auth:kas_client')->group(function () {
        Route::get('/client/dashboard', function () {
            return view('client.dashboard');
        })->name('client.dashboard');
    });

    // Impersonation helper — generates token and redirects to /impersonate/{rawToken}
    Route::get('kas-clients/{kasClient}/impersonate', [App\Http\Controllers\KasClientController::class, 'createImpersonationToken'])
        ->middleware(['auth', 'can:impersonate']) // we'll check admin inside controller too
        ->name('kas-clients.impersonate.generate');

    // Public endpoint that consumes token and logs in the kas_client guard
    Route::get('impersonate/{token}', [App\Http\Controllers\KasClientController::class, 'consumeImpersonationToken'])
        ->name('kas-clients.impersonate.consume');

