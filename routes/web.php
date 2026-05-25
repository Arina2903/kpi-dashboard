<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KpiController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
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
| FIRST TIME PASSWORD
|--------------------------------------------------------------------------
*/

Route::get('/create-password', [AuthController::class, 'firstPassword'])
    ->name('password.first');

Route::post('/create-password', [AuthController::class, 'storeFirstPassword'])
    ->name('password.first.submit');

/*
|--------------------------------------------------------------------------
| EMAIL VERIFICATION
|--------------------------------------------------------------------------
*/

Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])
    ->name('verify.email');

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['kpi.auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/choose-dashboard', [AuthController::class, 'showChooseDashboard'])
        ->name('dashboard.choose');

    Route::post('/choose-dashboard', [AuthController::class, 'selectDashboard'])
        ->name('dashboard.select');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::post('/switch-department', [DashboardController::class, 'switchDepartment'])
        ->name('switch.department');

    /*
    |--------------------------------------------------------------------------
    | KPI MAIN
    |--------------------------------------------------------------------------
    */

    Route::get('/kpi', [KpiController::class, 'index'])
        ->name('kpi.index');

    Route::get('/kpi/create', [KpiController::class, 'create'])
        ->name('kpi.create');

    Route::post('/kpi', [KpiController::class, 'store'])
        ->name('kpi.store');

    /*
    |--------------------------------------------------------------------------
    | KPI VIEW / EDIT
    |--------------------------------------------------------------------------
    */

    Route::get('/kpi/{id}/edit', [KpiController::class, 'edit'])
        ->name('kpi.edit');

    Route::put('/kpi/{id}', [KpiController::class, 'update'])
        ->name('kpi.update');

    Route::put('/kpi/{id}/inline-update', [KpiController::class, 'inlineUpdate'])
        ->name('kpi.update.inline');

    Route::delete('/kpi/{id}', [KpiController::class, 'destroy'])
        ->name('kpi.destroy');

    /*
    |--------------------------------------------------------------------------
    | KPI QUARTER
    |--------------------------------------------------------------------------
    */

    Route::post('/kpi/{kpiId}/quarters', [KpiController::class, 'storeQuarter'])
        ->name('kpi.quarters.store');

    Route::get('/kpi-quarter/{id}/edit', [KpiController::class, 'editQuarter'])
        ->name('kpi.quarter.edit');

    Route::post('/kpi-quarter/{id}/update', [KpiController::class, 'updateQuarter'])
        ->name('kpi.quarter.update');

    Route::post('/kpi-quarter/save', [KpiController::class, 'saveQuarter'])
        ->name('kpi.quarter.save');

    /*
    |--------------------------------------------------------------------------
    | KPI QUARTER UPDATE GOVERNANCE
    |--------------------------------------------------------------------------
    */

    Route::post('/kpi/update-quarter', [KpiController::class, 'updateQuarterActual'])
        ->name('kpi.quarter.actual.update');

    Route::post('/kpi/request-quarter-approval', [KpiController::class, 'requestQuarterApproval'])
        ->name('kpi.quarter.approval.request');

    /*
    |--------------------------------------------------------------------------
    | KPI GOVERNANCE REQUESTS
    |--------------------------------------------------------------------------
    */

    /*
    | REQUEST EDIT
    */

    Route::get('/kpi/{id}/request-edit', [KpiController::class, 'requestEdit'])
        ->name('kpi.request.edit');

    Route::post('/kpi/{id}/request-edit', [KpiController::class, 'submitEditRequest'])
        ->name('kpi.request.edit.submit');

    /*
    | REQUEST DELETE
    */

    Route::get('/kpi/{id}/request-delete', [KpiController::class, 'requestDelete'])
        ->name('kpi.request.delete');

    Route::post('/kpi/{id}/request-delete', [KpiController::class, 'submitDeleteRequest'])
        ->name('kpi.request.delete.submit');

    /*
    |--------------------------------------------------------------------------
    | KPI APPROVAL CENTER
    |--------------------------------------------------------------------------
    */

    Route::get('/kpi-approvals', [KpiController::class, 'approvalCenter'])
        ->name('kpi.approvals');

    /*
    |--------------------------------------------------------------------------
    | WEIGHTAGE
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/weightage/bulk-update',
        [KpiController::class, 'bulkUpdateWeightage']
    )->name('weightage.bulk-update');

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOG
    |--------------------------------------------------------------------------
    */

    Route::get('/activity-log', function () {
        return view('kpi.activity-log');
    });

    /*
    |--------------------------------------------------------------------------
    | MY DEPARTMENT KPI
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/my-department-kpi',
        [KpiController::class, 'myDepartmentKpi']
    )->name('kpi.my-department-kpi');

});
