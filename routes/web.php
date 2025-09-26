<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.2-alpha
 * @date      2025-09-24
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasClientController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocuController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Auth\LoginController;

// Dashboard
Route::get('/', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// Search
Route::get('/search', [SearchController::class, 'index'])
    ->middleware('auth')
    ->name('search');

// Accounts (KAS Clients)
Route::resource('kas-clients', KasClientController::class)
    ->middleware('auth');

// User Management
Route::resource('users', UserController::class)
    ->middleware('auth');

// Docs (statische Seiten)
Route::get('/docs', [DocuController::class, 'index'])
    ->middleware('auth')
    ->name('docs');

// Stats
Route::get('/stats', [StatsController::class, 'index'])
    ->middleware('auth')
    ->name('stats');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Batch-Operationen für KAS Clients
Route::post('/kas-clients/batch', [KasClientController::class, 'batch'])
    ->middleware('auth')
    ->name('kas-clients.batch');

// Batch-Operationen für User
Route::post('/users/batch', [UserController::class, 'batch'])
    ->middleware('auth')
    ->name('users.batch');

