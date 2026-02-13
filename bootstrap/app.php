<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclure les webhooks de la vérification CSRF
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'shwary/callback',
            'api/*',
        ]);

        // Load user relations early to avoid cache issues
        $middleware->appendToGroup('web', \App\Http\Middleware\LoadUserRelations::class);

        // IMPORTANT: Vérifier l'email AVANT tout autre vérification
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureEmailVerifiedBeforeAccess::class);

        // Add payment check middleware to web group (before other checks)
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureOrganizationIsPaid::class);

        // Add store access middleware to web group
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureUserHasStoreAccess::class);

        // Add organization access middleware to web group
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureOrganizationAccess::class);

        // Register middleware alias
        $middleware->alias([
            'organization' => \App\Http\Middleware\EnsureOrganizationAccess::class,
            'subscription.active' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'organization.paid' => \App\Http\Middleware\EnsureOrganizationIsPaid::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'feature' => \App\Http\Middleware\CheckPlanFeature::class,
            'resource.limit' => \App\Http\Middleware\CheckResourceLimit::class,
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimiter::class,
            'api.organization' => \App\Http\Middleware\EnsureApiOrganizationAccess::class,
            'api.permission' => \App\Http\Middleware\ApiCheckPermission::class,
            'stock.required' => \App\Http\Middleware\EnsureStockManagement::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
