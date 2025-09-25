<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.0-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasClientController;
use App\Http\Controllers\Auth\LoginController;

// Startseite -> Dashboard (nur für eingeloggte User)
Route::get('/', function () {
    return view('dashboard'); // später echte Dashboard-View
})->middleware('auth')->name('dashboard');

// Login Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Kas Client Management (geschützt)
Route::middleware('auth')->group(function () {
    Route::resource('kas-clients', KasClientController::class);
});

// Test-Layout Route (nur dev)
Route::get('/test-layout', function () {
    return view('test');
});
