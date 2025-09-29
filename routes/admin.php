<?php
/**
 * R3D KAS Manager – Application Bootstrap
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.9.2-alpha
 * @date      2025-09-29
 * @license   MIT License
 *
 * routes\admin.php
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasClientController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocuController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SearchController;

Route::middleware(['web_admin','auth:web'])->group(function () {
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
