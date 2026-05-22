<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KpiController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Auth)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'submitLogin'])
    ->name('login.submit');

/*
|--------------------------------------------------------------------------
| First Time Setup / Email
|--------------------------------------------------------------------------
*/

Route::get('/create-password', [AuthController::class, 'firstPassword'])
    ->name('password.first');

Route::post('/create-password', [AuthController::class, 'storeFirstPassword'])
    ->name('password.first.submit');

Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])
    ->name('verify.email');

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');


/*
|--------------------------------------------------------------------------
| Protected Routes (WITH middleware)
|--------------------------------------------------------------------------
*/

Route::middleware(['kpi.auth'])->group(function () {

    // DASHBOARD
    Route::get('/choose-dashboard', [AuthController::class, 'showChooseDashboard'])
        ->name('dashboard.choose');

    Route::post('/choose-dashboard', [AuthController::class, 'selectDashboard'])
        ->name('dashboard.select');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::post('/switch-department', [DashboardController::class, 'switchDepartment'])
        ->name('switch.department');

    // KPI
    Route::get('/kpi', [KpiController::class, 'index'])->name('kpi.index');
    Route::get('/kpi/create', [KpiController::class, 'create'])->name('kpi.create');
    Route::post('/kpi', [KpiController::class, 'store'])->name('kpi.store');
    Route::get('/kpi/{id}/edit', [KpiController::class, 'edit'])->name('kpi.edit');
    Route::put('/kpi/{id}', [KpiController::class, 'update'])->name('kpi.update');
    Route::put('/kpi/{id}/inline-update', [KpiController::class, 'inlineUpdate'])->name('kpi.update.inline');
    Route::delete('/kpi/{id}', [KpiController::class, 'destroy'])->name('kpi.destroy');

    // KPI QUARTER
    Route::post('/kpi/{kpiId}/quarters', [KpiController::class, 'storeQuarter'])
        ->name('kpi.quarters.store');

    Route::get('/kpi-quarter/{id}/edit', [KpiController::class, 'editQuarter'])
        ->name('kpi.quarter.edit');

    Route::post('/kpi-quarter/{id}/update', [KpiController::class, 'updateQuarter'])
        ->name('kpi.quarter.update');

    Route::post('/kpi-quarter/save', [KpiController::class, 'saveQuarter'])
        ->name('kpi.quarter.save');

    // KPI TARGET REQUEST
    Route::post('/kpi/{id}/target-change-request', [KpiController::class, 'requestTargetChange'])
        ->name('kpi.target.request');

    Route::post('/kpi-target-request/{id}/approve', [KpiController::class, 'approveTargetChange'])
        ->name('kpi.target.approve');

    Route::post('/kpi-target-request/{id}/reject', [KpiController::class, 'rejectTargetChange'])
        ->name('kpi.target.reject');
});
