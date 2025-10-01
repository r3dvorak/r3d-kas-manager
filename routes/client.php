<?php
/**
 * R3D KAS Manager – Application Bootstrap
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.10.3-alpha
 * @date      2025-09-29
 * @license   MIT License
 *
 * routes\client.php
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\DomainController;
use App\Http\Controllers\Client\MailboxController;
use App\Http\Controllers\Client\DnsController;
use App\Http\Controllers\Client\RecipeController;

Route::prefix('client')->name('client.')->middleware(['web_client','auth:kas_client'])->group(function () {
    Route::get('/dashboard', fn() => view('client.dashboard'))->name('dashboard');
    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::get('/mailboxes', [MailboxController::class, 'index'])->name('mailboxes.index');
    Route::get('/dns', [DnsController::class, 'index'])->name('dns.index');
    Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
});
