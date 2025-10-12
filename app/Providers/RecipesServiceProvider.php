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
 * @version   0.26.8-alpha
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

class RecipesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Optionally bind KasGateway if available:
        $kasClass = 'App\\Services\\Kas\\KasGateway';
        if (class_exists($kasClass)) {
            $this->app->singleton($kasClass, function ($app) use ($kasClass) {
                // if the KasGateway expects config array, adapt as needed
                return new $kasClass(config('services.kas') ?? []);
            });
        }

        // Handlers list (Dispatcher will instantiate them via container)
        $handlers = [
            AddDomain::class,
            UpdateDnsRecords::class,
            AddMailaccount::class,
            AddMailforward::class,
        ];

        // Bind the Dispatcher with handler list
        $this->app->singleton(Dispatcher::class, function ($app) use ($handlers) {
            return new Dispatcher($app, $handlers);
        });

        // If you want handlers resolvable elsewhere, you can also register them
        // using the class shorthand (no closure required):
        foreach ($handlers as $h) {
            $this->app->singleton($h);
        }
    }

    public function boot()
    {
        //
    }
}
