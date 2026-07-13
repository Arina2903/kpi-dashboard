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
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'kpi.auth' => \App\Http\Middleware\KpiAuth::class,
            'telegram.webapp.auth' => \App\Http\Middleware\TelegramWebAppAuth::class,
            'telegram.webhook.secret' => \App\Http\Middleware\TelegramWebhookSecret::class,
            'telegram.cron.secret' => \App\Http\Middleware\TelegramCronSecret::class,
            'no-cache' => \App\Http\Middleware\NoCacheHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
