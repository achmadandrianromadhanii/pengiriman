<?php

use App\Http\Middleware\EnsureAdminActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecureHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,

            // Security headers (X-Frame-Options, nosniff, referrer-policy, dll)
            SecureHeaders::class,
        ]);

        $middleware->alias([
            'admin.active' => EnsureAdminActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

if (isset($_ENV['APP_STORAGE_PATH'])) {
    $app->useStoragePath($_ENV['APP_STORAGE_PATH']);
}

return $app;
