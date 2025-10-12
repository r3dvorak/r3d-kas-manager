<?php
/**
 * RecipesServiceProvider
 *
 * Binds the KasGateway, Action Handlers and Dispatcher into the container so
 * the RecipeExecutor can resolve them via DI. Register this provider in
 * config/app.php (providers array) or via package discovery as appropriate.
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.4
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Providers/RecipesServiceProvider.php
 *
 * Responsibilities:
 *  - Register a singleton KasGateway
 *  - Register action handler singletons (AddDomain, UpdateDnsRecords,
 *    AddMailaccount, AddMailforward)
 *  - Register a Dispatcher singleton wired with the above handlers
 *
 * Notes:
 *  - This provider makes the Dispatcher available through the container so
 *    RecipeExecutor can be resolved with DI (app()->make(App\Services\RecipeExecutor::class))
 *  - After dropping this file in, run:
 *      composer dump-autoload
 *      php artisan optimize:clear
 *  - Add to config/app.php providers array:
 *      App\Providers\RecipesServiceProvider::class,
 */


namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Recipes\KasGateway;
use App\Services\Recipes\Dispatcher;
use App\Services\Recipes\Actions\AddDomain;
use App\Services\Recipes\Actions\UpdateDnsRecords;
use App\Services\Recipes\Actions\AddMailaccount;
use App\Services\Recipes\Actions\AddMailforward;

class RecipesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Single shared KasGateway instance
        $this->app->singleton(KasGateway::class, function ($app) {
            return new KasGateway();
        });

        // Register action handlers as singletons
        $this->app->singleton(AddDomain::class, function ($app) {
            return new AddDomain($app->make(KasGateway::class));
        });
        $this->app->singleton(UpdateDnsRecords::class, function ($app) {
            return new UpdateDnsRecords($app->make(KasGateway::class));
        });
        $this->app->singleton(AddMailaccount::class, function ($app) {
            return new AddMailaccount($app->make(KasGateway::class));
        });
        $this->app->singleton(AddMailforward::class, function ($app) {
            return new AddMailforward($app->make(KasGateway::class));
        });

        // Dispatcher: resolved with the handlers from container
        $this->app->singleton(Dispatcher::class, function ($app) {
            $handlers = [
                $app->make(AddDomain::class),
                $app->make(UpdateDnsRecords::class),
                $app->make(AddMailaccount::class),
                $app->make(AddMailforward::class),
            ];
            return new Dispatcher($handlers);
        });
    }

    public function boot()
    {
        // nothing to boot yet
    }
}
