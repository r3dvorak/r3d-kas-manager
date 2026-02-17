<?php
/**
 * R3D KAS Manager – Application Bootstrap
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.30.2-alpha
 * @date      2025-09-28
 * @license   MIT License
 *
 * bootstrap/app.php
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

require_once __DIR__ . '/../app/Support/helpers.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // === Global middleware ===
        $middleware->append(Illuminate\Http\Middleware\TrustProxies::class);
        $middleware->append(App\Http\Middleware\ConfigureWorkspaceSessionCookie::class);
        $middleware->append(Illuminate\Foundation\Http\Middleware\TrimStrings::class);
        $middleware->append(Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class);

        // === Web group ===
        $middleware->group('web', [
            Illuminate\Cookie\Middleware\EncryptCookies::class,
            Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            App\Http\Middleware\ResolveWorkspace::class,
            Illuminate\Session\Middleware\StartSession::class,
            App\Http\Middleware\ResolveLocale::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
            Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
            App\Http\Middleware\AppendWorkspaceToRedirects::class,
        ]);

        // === API group ===
        $middleware->group('api', [
            'throttle:api',
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // === Aliases ===
        $middleware->alias([
            'auth'     => Illuminate\Auth\Middleware\Authenticate::class,
            'guest'    => Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'useguard' => App\Http\Middleware\UseGuardSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
