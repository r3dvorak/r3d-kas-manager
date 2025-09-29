<?php
/**
 * R3D KAS Manager – Application Bootstrap
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.9.1-alpha
 * @date      2025-09-29
 * @license   MIT License
 *
 * bootstrap/app.php
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php', // nur Login / Public
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function ($router) {
            // Admin-Routen laden
            require __DIR__.'/../routes/admin.php';
            // Client-Routen laden
            require __DIR__.'/../routes/client.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global Middleware
        $middleware->append(Illuminate\Http\Middleware\TrustProxies::class);
        $middleware->append(Illuminate\Foundation\Http\Middleware\TrimStrings::class);
        $middleware->append(Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class);

        // Admin Session group
        $middleware->group('web_admin', [
            App\Http\Middleware\AdminSessionConfig::class,
            Illuminate\Cookie\Middleware\EncryptCookies::class,
            Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Illuminate\Session\Middleware\StartSession::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
            Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Client Session group
        $middleware->group('web_client', [
            App\Http\Middleware\ClientSessionConfig::class,
            Illuminate\Cookie\Middleware\EncryptCookies::class,
            Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Illuminate\Session\Middleware\StartSession::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
            Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

