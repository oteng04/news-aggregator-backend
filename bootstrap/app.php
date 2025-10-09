<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'throttle' => \App\Http\Middleware\RateLimitMiddleware::class,
            'api.logging' => \App\Http\Middleware\ApiLoggingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\App\Exceptions\NewsApiException $e) {
            return $e->render(request());
        });
    })
    ->create();

// Register additional service providers
$app->register(\Illuminate\Session\SessionServiceProvider::class);
$app->register(\Illuminate\View\ViewServiceProvider::class);
$app->register(\Illuminate\Broadcasting\BroadcastServiceProvider::class);

return $app;
