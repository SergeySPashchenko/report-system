<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Rate limiting для API
        RateLimiter::for('api', fn (Request $request) => $request->user()
            ? Limit::perMinute(60)->by($request->user()->id)
            : Limit::perMinute(20)->by($request->ip()));

        // Strict rate limiting для auth endpoints
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
