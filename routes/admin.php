<?php
/**
 * R3D KAS Manager – Application Bootstrap
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.10.5-alpha
 * @date      2025-09-29
 * @license   MIT License
 *
 * routes\admin.php
 */

use Illuminate\Support\Facades\Route;

// All admin routes are protected by Admin session + web guard
Route::prefix('admin')
    ->middleware(['web_admin', 'auth:web'])
    ->group(function () {
        Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
        Route::get('/search', [App\Http\Controllers\SearchController::class, 'index'])->name('search');
        Route::resource('kas-clients', App\Http\Controllers\KasClientController::class);
        Route::post('kas-clients/batch', [App\Http\Controllers\KasClientController::class, 'batch'])->name('kas-clients.batch');
        Route::resource('users', App\Http\Controllers\UserController::class);
        Route::post('users/batch', [App\Http\Controllers\UserController::class, 'batch'])->name('users.batch');
        Route::get('/docs', [App\Http\Controllers\DocuController::class, 'index'])->name('docs');
        Route::get('/stats', [App\Http\Controllers\StatsController::class, 'index'])->name('stats');
        Route::get('kas-clients/{kasClient}/impersonate', [App\Http\Controllers\KasClientController::class, 'createImpersonationToken'])
            ->middleware('can:impersonate')
            ->name('kas-clients.impersonate.generate');
    });

