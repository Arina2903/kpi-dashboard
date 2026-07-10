<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Telegram\TelegramWebhookController;
use App\Http\Controllers\Telegram\TelegramMiniAppController;
use App\Http\Controllers\Telegram\TelegramLinkController;
use App\Http\Controllers\Telegram\TelegramCronController;

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware('telegram.webhook.secret');

Route::middleware('telegram.cron.secret')->prefix('telegram/cron')->group(function () {
    Route::post('/morning', [TelegramCronController::class, 'morning']);
    Route::post('/evening', [TelegramCronController::class, 'evening']);
});

Route::middleware('telegram.webapp.auth')->prefix('telegram')->group(function () {
    Route::get('/kpis/open', [TelegramMiniAppController::class, 'openKpis']);
    Route::get('/kpis/summary', [TelegramMiniAppController::class, 'summary']);
    Route::post('/tasks', [TelegramMiniAppController::class, 'storeTasks']);
    Route::get('/tasks/today', [TelegramMiniAppController::class, 'todayTasks']);
    Route::post('/tasks/{id}/progress', [TelegramMiniAppController::class, 'submitProgress']);
    Route::get('/link/status', [TelegramLinkController::class, 'status']);
    Route::post('/link/disconnect', [TelegramLinkController::class, 'disconnect']);
});
