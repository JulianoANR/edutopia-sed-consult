<?php

namespace App\Providers;

use App\Services\SedApiService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use App\Services\SedEscolasService;
use App\Services\SedTurmasService;
use App\Services\SedAlunosService;
use App\Services\SedDadosBasicosService;

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

        // Register new specific SED services as singletons
        $this->app->singleton(SedEscolasService::class, function (Application $app) {
            return new SedEscolasService($app->make(SedApiService::class));
        });
        $this->app->singleton(SedTurmasService::class, function (Application $app) {
            return new SedTurmasService($app->make(SedApiService::class));
        });
        $this->app->singleton(SedAlunosService::class, function (Application $app) {
            return new SedAlunosService($app->make(SedApiService::class));
        });
        $this->app->singleton(SedDadosBasicosService::class, function (Application $app) {
            return new SedDadosBasicosService($app->make(SedApiService::class));
        });
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
            SedEscolasService::class,
            SedTurmasService::class,
            SedAlunosService::class,
            SedDadosBasicosService::class,
        ];
    }
}