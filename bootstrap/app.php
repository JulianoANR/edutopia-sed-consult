<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\ClearUserCache::class,
            \App\Http\Middleware\RefreshCsrfToken::class,
        ]);

        // Register route middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Exceções CSRF para rotas da API SED
        $middleware->validateCsrfTokens(except: [
            'sed-api/*',
            'classes/export-excel',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
