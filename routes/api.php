<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Telegram\TelegramWebhookController;
use App\Http\Controllers\Telegram\TelegramMiniAppController;
use App\Http\Controllers\Telegram\TelegramLinkController;
use App\Http\Controllers\Telegram\TelegramCronController;
use App\Http\Controllers\Telegram\TelegramProjectTaskController;

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware('telegram.webhook.secret');

Route::middleware('telegram.cron.secret')->prefix('telegram/cron')->group(function () {
    Route::post('/morning', [TelegramCronController::class, 'morning']);
    Route::post('/evening', [TelegramCronController::class, 'evening']);
    Route::post('/review/{period}', [TelegramCronController::class, 'review']);
});

Route::middleware('telegram.webapp.auth')->prefix('telegram')->group(function () {
    Route::get('/kpis/open', [TelegramMiniAppController::class, 'openKpis']);
    Route::get('/kpis/summary', [TelegramMiniAppController::class, 'summary']);
    Route::post('/tasks', [TelegramMiniAppController::class, 'storeTasks']);
    Route::get('/tasks/today', [TelegramMiniAppController::class, 'todayTasks']);
    Route::post('/tasks/{id}/progress', [TelegramMiniAppController::class, 'submitProgress']);
    Route::post('/kpis/{kpiId}/quarters/{quarterId}/adjust', [TelegramMiniAppController::class, 'adjustQuarter']);
    Route::get('/link/status', [TelegramLinkController::class, 'status']);
    Route::post('/link/disconnect', [TelegramLinkController::class, 'disconnect']);

    Route::get('/projects', [TelegramProjectTaskController::class, 'listProjects']);
    Route::post('/projects', [TelegramProjectTaskController::class, 'createProject']);
    Route::get('/project-tasks', [TelegramProjectTaskController::class, 'listTasks']);
    Route::post('/project-tasks', [TelegramProjectTaskController::class, 'createTask']);
    Route::get('/project-tasks/kpi-options', [TelegramProjectTaskController::class, 'kpiOptions']);
    Route::post('/project-tasks/{id}/link-kpis', [TelegramProjectTaskController::class, 'linkKpis']);
    Route::post('/project-tasks/{id}/progress', [TelegramProjectTaskController::class, 'updateProgress']);
    Route::get('/kpis/{kpiId}/task-history', [TelegramProjectTaskController::class, 'kpiTaskHistory']);

    Route::get('/reviews', [TelegramMiniAppController::class, 'reviews']);
});
