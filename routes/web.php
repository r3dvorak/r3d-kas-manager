<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.5-alpha
 * @date      2025-09-26
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

// Auth routes (Login, Logout etc.)
require __DIR__.'/auth.php';
