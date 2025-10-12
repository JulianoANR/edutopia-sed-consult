<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Gate: somente super admin pode gerenciar integrações de tenants
        Gate::define('manage-tenants', function (User $user) {
            return method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false;
        });
    }
}
