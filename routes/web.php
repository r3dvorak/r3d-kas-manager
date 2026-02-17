<?php
/**
 * R3D KAS Manager – Web Routes
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.28.13-alpha
 * @date      2025-10-05
 * 
 * @license   MIT License
 * @copyright (C) 2025
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasClientController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocuController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Auth\UnifiedLoginController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\MailboxesController;
use App\Http\Controllers\Admin\MailforwardsController;

// ============================================================
// === Unified Login / Logout ===
// ============================================================

Route::get('/login', [UnifiedLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UnifiedLoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout');
Route::get('/locale/{locale}', function (string $locale) {
    if (!in_array($locale, ['de', 'en'], true)) {
        abort(404);
    }

    session(['locale' => $locale]);
    app()->setLocale($locale);

    return redirect()->back();
})->name('locale.switch');

// ============================================================
// === Configuration ===
// ============================================================

Route::middleware(['web','auth:web','can:access-admin-panel'])->group(function () {
    Route::get('/config', [ConfigController::class, 'index'])->name('config.index');
    Route::post('/config', [ConfigController::class, 'update'])->name('config.update');
});

// ============================================================
// === Admin routes (web guard) ===
// ============================================================

Route::middleware(['web', 'useguard:web', 'auth:web', 'can:access-admin-panel'])->group(function () {

    Route::get('/', fn() => view('dashboard'))->name('dashboard');

    Route::get('/search', [SearchController::class, 'index'])->name('search');

    Route::resource('kas-clients', KasClientController::class);
    Route::post('kas-clients/batch', [KasClientController::class, 'batch'])->name('kas-clients.batch');

    // Mail (admin)
    Route::get('/mailboxes', [MailboxesController::class, 'index'])->name('admin.mailboxes.index');
    Route::get('/mailboxes/create', [MailboxesController::class, 'create'])->name('admin.mailboxes.create');
    Route::post('/mailboxes', [MailboxesController::class, 'store'])->name('admin.mailboxes.store');
    Route::get('/mailboxes/{mailbox}/edit', [MailboxesController::class, 'edit'])->name('admin.mailboxes.edit');
    Route::put('/mailboxes/{mailbox}', [MailboxesController::class, 'update'])->name('admin.mailboxes.update');
    Route::delete('/mailboxes/{mailbox}', [MailboxesController::class, 'destroy'])->name('admin.mailboxes.destroy');
    Route::get('/mailboxes/preview', [MailboxesController::class, 'preview'])->name('admin.mailboxes.preview');
    Route::post('/mailboxes/sync', [MailboxesController::class, 'sync'])->name('admin.mailboxes.sync');

    Route::get('/mailforwards', [MailforwardsController::class, 'index'])->name('admin.mailforwards.index');
    Route::get('/mailforwards/create', [MailforwardsController::class, 'create'])->name('admin.mailforwards.create');
    Route::post('/mailforwards', [MailforwardsController::class, 'store'])->name('admin.mailforwards.store');
    Route::get('/mailforwards/{forward}/edit', [MailforwardsController::class, 'edit'])->name('admin.mailforwards.edit');
    Route::put('/mailforwards/{forward}', [MailforwardsController::class, 'update'])->name('admin.mailforwards.update');
    Route::delete('/mailforwards/{forward}', [MailforwardsController::class, 'destroy'])->name('admin.mailforwards.destroy');
    Route::get('/mailforwards/preview', [MailforwardsController::class, 'preview'])->name('admin.mailforwards.preview');
    Route::post('/mailforwards/sync', [MailforwardsController::class, 'sync'])->name('admin.mailforwards.sync');

    Route::resource('users', UserController::class);
    Route::post('users/batch', [UserController::class, 'batch'])->name('users.batch');

    Route::get('/docs', [DocuController::class, 'index'])->name('docs');
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');
        
    Route::get('/config', [ConfigController::class, 'index'])->name('config.index');
    Route::post('/config', [ConfigController::class, 'update'])->name('config.update');

    //Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    //Route::post('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');

    Route::get('kas-clients/{kasClient}/impersonate', [KasClientController::class, 'createImpersonationToken'])
        ->middleware('can:impersonate')
        ->name('kas-clients.impersonate.generate');

});


// ============================================================
// === Client routes (kas_client guard) ===
// ============================================================

Route::prefix('client')->name('client.')->middleware(['web', 'useguard:kas_client', 'auth:kas_client'])->group(function () {

    Route::get('/dashboard', fn() => view('client.dashboard'))->name('dashboard');

    Route::get('/domains', [App\Http\Controllers\Client\DomainController::class, 'index'])->name('domains.index');
    Route::get('/domains/preview', [App\Http\Controllers\Client\DomainController::class, 'preview'])->name('domains.preview');
    Route::post('/domains/sync', [App\Http\Controllers\Client\DomainController::class, 'sync'])->name('domains.sync');

    Route::get('/subdomains', [App\Http\Controllers\Client\SubdomainController::class, 'index'])->name('subdomains.index');
    Route::get('/subdomains/preview', [App\Http\Controllers\Client\SubdomainController::class, 'preview'])->name('subdomains.preview');
    Route::post('/subdomains/sync', [App\Http\Controllers\Client\SubdomainController::class, 'sync'])->name('subdomains.sync');

    Route::get('/mailboxes', [App\Http\Controllers\Client\MailboxController::class, 'index'])->name('mailboxes.index');
    Route::get('/mailboxes/preview', [App\Http\Controllers\Client\MailboxController::class, 'preview'])->name('mailboxes.preview');
    Route::post('/mailboxes/sync', [App\Http\Controllers\Client\MailboxController::class, 'sync'])->name('mailboxes.sync');

    Route::get('/mailforwards', [App\Http\Controllers\Client\MailforwardController::class, 'index'])->name('mailforwards.index');
    Route::get('/mailforwards/preview', [App\Http\Controllers\Client\MailforwardController::class, 'preview'])->name('mailforwards.preview');
    Route::post('/mailforwards/sync', [App\Http\Controllers\Client\MailforwardController::class, 'sync'])->name('mailforwards.sync');

    Route::get('/ftp', [App\Http\Controllers\Client\FtpController::class, 'index'])->name('ftp.index');
    Route::get('/ftp/preview', [App\Http\Controllers\Client\FtpController::class, 'preview'])->name('ftp.preview');
    Route::post('/ftp/sync', [App\Http\Controllers\Client\FtpController::class, 'sync'])->name('ftp.sync');

    Route::get('/databases', [App\Http\Controllers\Client\DatabaseController::class, 'index'])->name('databases.index');
    Route::get('/databases/preview', [App\Http\Controllers\Client\DatabaseController::class, 'preview'])->name('databases.preview');
    Route::post('/databases/sync', [App\Http\Controllers\Client\DatabaseController::class, 'sync'])->name('databases.sync');

    Route::get('/ssl', [App\Http\Controllers\Client\SslController::class, 'index'])->name('ssl.index');
    Route::get('/ssl/preview', [App\Http\Controllers\Client\SslController::class, 'preview'])->name('ssl.preview');
    Route::post('/ssl/sync', [App\Http\Controllers\Client\SslController::class, 'sync'])->name('ssl.sync');

    Route::get('/statistics', [App\Http\Controllers\Client\StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/statistics/preview', [App\Http\Controllers\Client\StatisticsController::class, 'preview'])->name('statistics.preview');
    Route::post('/statistics/sync', [App\Http\Controllers\Client\StatisticsController::class, 'sync'])->name('statistics.sync');

    Route::get('/dns', [App\Http\Controllers\Client\DnsController::class, 'index'])->name('dns.index');
    Route::get('/dns/create', [App\Http\Controllers\Client\DnsController::class, 'create'])->name('dns.create');
    Route::post('/dns', [App\Http\Controllers\Client\DnsController::class, 'store'])->name('dns.store');
    Route::get('/dns/{dnsRecord}/edit', [App\Http\Controllers\Client\DnsController::class, 'edit'])->name('dns.edit');
    Route::put('/dns/{dnsRecord}', [App\Http\Controllers\Client\DnsController::class, 'update'])->name('dns.update');
    Route::delete('/dns/{dnsRecord}', [App\Http\Controllers\Client\DnsController::class, 'destroy'])->name('dns.destroy');
    Route::get('/dns/preview', [App\Http\Controllers\Client\DnsController::class, 'preview'])->name('dns.preview');
    Route::post('/dns/sync', [App\Http\Controllers\Client\DnsController::class, 'sync'])->name('dns.sync');

    Route::get('/mailboxes/create', [App\Http\Controllers\Client\MailboxController::class, 'create'])->name('mailboxes.create');
    Route::post('/mailboxes', [App\Http\Controllers\Client\MailboxController::class, 'store'])->name('mailboxes.store');
    Route::get('/mailboxes/{mailbox}/edit', [App\Http\Controllers\Client\MailboxController::class, 'edit'])->name('mailboxes.edit');
    Route::put('/mailboxes/{mailbox}', [App\Http\Controllers\Client\MailboxController::class, 'update'])->name('mailboxes.update');
    Route::post('/mailboxes/{mailbox}/toggle-state', [App\Http\Controllers\Client\MailboxController::class, 'toggleState'])->name('mailboxes.toggle-state');
    Route::delete('/mailboxes/{mailbox}', [App\Http\Controllers\Client\MailboxController::class, 'destroy'])->name('mailboxes.destroy');
    Route::get('/recipes', [App\Http\Controllers\Client\RecipeController::class, 'index'])->name('recipes.index');
    Route::get('/launch/{tool}', [App\Http\Controllers\Client\ExternalLaunchController::class, 'create'])->name('launch.create');
});

// ============================================================
// === Impersonation public endpoints ===
// ============================================================

Route::get('impersonate/{token}', [KasClientController::class, 'consumeImpersonationToken'])
    ->name('kas-clients.impersonate.consume');

Route::post('kas-clients/impersonate/leave', [KasClientController::class, 'leaveImpersonation'])
    ->middleware(['web', 'useguard:kas_client', 'auth:kas_client'])
    ->name('kas-clients.impersonate.leave');

Route::get('launch/{token}', [App\Http\Controllers\Client\ExternalLaunchController::class, 'consume'])
    ->middleware('web')
    ->name('external-launch.consume');
