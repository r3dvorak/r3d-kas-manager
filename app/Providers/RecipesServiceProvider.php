<?php
/**
 * RecipesServiceProvider
 *
 * Registers KasGateway, action handlers, and Dispatcher into the container.
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.10-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Providers/RecipesServiceProvider.php
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Recipes\KasGateway;
use App\Services\Recipes\Dispatcher;
use App\Services\Recipes\Actions\AddDomain;
use App\Services\Recipes\Actions\AddMailaccount;
use App\Services\Recipes\Actions\AddMailforward;
use App\Services\Recipes\Actions\UpdateDnsRecords;

class RecipesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // --- Bind KasGateway (correct namespace) ---
        $this->app->singleton(KasGateway::class, function ($app) {
            return new KasGateway();
        });

        // --- Handlers list (Dispatcher will use them) ---
        $handlers = [
            AddDomain::class,
            UpdateDnsRecords::class,
            AddMailaccount::class,
            AddMailforward::class,
        ];

        // --- Bind Dispatcher wired with handlers ---
        $this->app->singleton(Dispatcher::class, function ($app) use ($handlers) {
            return new Dispatcher($app, $handlers);
        });

        // --- Also make handlers individually resolvable ---
        foreach ($handlers as $h) {
            $this->app->singleton($h);
        }
    }

    public function boot()
    {
        // nothing to boot yet
    }
}
