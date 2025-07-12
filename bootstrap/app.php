<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleMethodSpoofing;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\RedirectBasedOnRole;
use App\Http\Middleware\RoleBasedUIRestriction;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Replace default CSRF middleware with custom one
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => VerifyCsrfToken::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            HandleMethodSpoofing::class,
            RoleBasedUIRestriction::class,
            AddLinkHeadersForPreloadedAssets::class,
            RedirectBasedOnRole::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
