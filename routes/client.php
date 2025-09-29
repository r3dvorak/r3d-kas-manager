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
 * routes\client.php
 */

use Illuminate\Support\Facades\Route;

Route::prefix('client')->name('client.')->middleware(['web_client','auth:kas_client'])->group(function () {
    Route::get('/dashboard', fn() => view('client.dashboard'))->name('dashboard');

    Route::get('/domains', [App\Http\Controllers\Client\DomainController::class, 'index'])->name('domains.index');
    Route::get('/mailboxes', [App\Http\Controllers\Client\MailboxController::class, 'index'])->name('mailboxes.index');
    Route::get('/dns', [App\Http\Controllers\Client\DnsController::class, 'index'])->name('dns.index');
    Route::get('/recipes', [App\Http\Controllers\Client\RecipeController::class, 'index'])->name('recipes.index');
});
