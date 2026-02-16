<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.6.8-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * app\Providers\AuthServiceProvider.php with impersonation Gate
 */

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\KasClient;
use App\Models\User;
use App\Policies\KasClientPolicy;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        KasClient::class => KasClientPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Admin-Check für Impersonation
        Gate::define('impersonate', function (User $user) {
            return $user->role === 'admin' || $user->is_admin === 1;
        });

        Gate::define('access-admin-panel', function (User $user) {
            return $user->role === 'admin' || $user->is_admin === 1;
        });
    }
}
