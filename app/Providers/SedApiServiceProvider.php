<?php

namespace App\Providers;

use App\Services\SedApiService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class SedApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the SED API Service as a singleton
        $this->app->singleton(SedApiService::class, function (Application $app) {
            return new SedApiService();
        });

        // Register an alias for easier access
        $this->app->alias(SedApiService::class, 'sed-api');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../../config/sed.php' => config_path('sed.php'),
        ], 'sed-config');

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/sed.php',
            'sed'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            SedApiService::class,
            'sed-api',
        ];
    }
}