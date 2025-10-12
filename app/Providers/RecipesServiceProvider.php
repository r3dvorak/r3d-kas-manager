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
 * @version   0.26.7-alpha
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
use App\Services\Recipes\Dispatcher;
use App\Services\Recipes\Actions\AddDomain;
use App\Services\Recipes\Actions\AddMailaccount;
use App\Services\Recipes\Actions\AddMailforward;
use App\Services\Recipes\Actions\UpdateDnsRecords;
use App\Services\Kas\KasGateway;

class RecipesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register KasGateway (example singleton binding)
        $this->app->singleton(KasGateway::class, function ($app) {
            // adapt constructor args as needed; keep minimal here
            return new KasGateway(config('services.kas'));
        });

        // Register handler classes (class names only; Dispatcher will instantiate them)
        $handlers = [
            AddDomain::class,
            UpdateDnsRecords::class,
            AddMailaccount::class,
            AddMailforward::class,
        ];

        // Bind the Dispatcher with the handler list
        $this->app->singleton(Dispatcher::class, function ($app) use ($handlers) {
            return new Dispatcher($app, $handlers);
        });

        // Optionally bind handlers individually if other code resolves them directly
        foreach ($handlers as $handlerClass) {
            $this->app->singleton($handlerClass, function ($app) use ($handlerClass) {
                return $app->make($handlerClass);
            });
        }
    }

    public function boot()
    {
        // nothing special needed here for the Dispatcher change
    }
}
