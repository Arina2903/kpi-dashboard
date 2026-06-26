<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\KpiTemplateController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiController;

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

/*
|--------------------------------------------------------------------------
| COMPANY SELECTION (no employee session needed yet — set after choosing)
|--------------------------------------------------------------------------
*/

Route::get('/choose-dashboard', [AuthController::class, 'showChooseDashboard'])
    ->name('dashboard.choose');

Route::post('/choose-dashboard', [AuthController::class, 'selectDashboard'])
    ->name('dashboard.select');

Route::middleware(['kpi.auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

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

    Route::post('/kpi/assignment/{id}/accept', [KpiController::class, 'acceptAssignment'])
        ->name('kpi.assignment.accept');

    Route::post('/kpi/assignment/{id}/reject', [KpiController::class, 'rejectAssignment'])
        ->name('kpi.assignment.reject');

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

    Route::post(
        '/kpi/update-quarter',
        [KpiController::class, 'updateQuarterActual']
    )->name('kpi.quarter.actual.update');

    Route::post(
        '/kpi/request-quarter-approval',
        [KpiController::class, 'requestQuarterApproval']
    )->name('kpi.quarter.approval.request');

    /*
    |--------------------------------------------------------------------------
    | ACTUAL CHANGE REQUEST
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/kpi/{kpiId}/quarter/{quarterId}/actual-request',
        [KpiController::class, 'submitActualUpdateRequest']
    )->name('kpi.actual.request');

    Route::post(
        '/kpi/quarter/{quarterId}/status',
        [KpiController::class, 'saveQuarterStatus']
    )->name('kpi.quarter.status');

    Route::put(
        '/kpi/quarter/{id}/inline-update',
        [KpiController::class, 'inlineUpdateQuarter']
    )->name('kpi.quarter.inline-update');

    Route::post(
        '/kpi/quarter/{id}/complete',
        [KpiController::class, 'completeQuarter']
    )->name('kpi.quarter.complete');

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
    | REQUEST TARGET CHANGE
    */

    Route::post(

        '/kpi/{id}/request-target-change',

        [KpiController::class,
        'requestTargetChange']

    )->name(
        'kpi.requestTargetChange'
    );

    Route::post(
        '/kpi/{id}/request-weightage-change',
        [
            ApprovalController::class,
            'requestWeightageChange'
        ]
    )->name(
        'kpi.request-weightage-change'
    );

    /*
    | REQUEST DELETE
    */

    Route::get('/kpi/{id}/request-delete', [KpiController::class, 'requestDelete'])
        ->name('kpi.request.delete');

    Route::post('/kpi/{id}/request-delete', [KpiController::class, 'submitDeleteRequest'])
        ->name('kpi.request.delete.submit');

    /*
    |--------------------------------------------------------------------------
    | APPROVAL CENTER
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/approval',
        [ApprovalController::class, 'index']
    )->name('approval.index');

    Route::post(
        '/approval/{id}/approve',
        [ApprovalController::class, 'approve']
    )->name('approval.approve');

    Route::post(
        '/approval/{id}/reject',
        [ApprovalController::class, 'reject']
    )->name('approval.reject');

    Route::get(
        '/approval/rejected',
        [ApprovalController::class,'rejected']
    )->name('approval.rejected');

    /*
    |--------------------------------------------------------------------------
    | WEIGHTAGE
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/weightage',
        [KpiController::class, 'weightage']
    )->name('weightage');

    Route::post(
        '/weightage/bulk-update',
        [KpiController::class, 'bulkUpdateWeightage']
    )->name('weightage.bulk-update');

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOG
    |--------------------------------------------------------------------------
    */

    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log');

    /*
    |--------------------------------------------------------------------------
    | PERFORMANCE EVALUATION
    |--------------------------------------------------------------------------
    */
    Route::get('/attendance',             [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/import',     [\App\Http\Controllers\AttendanceController::class, 'import'])->name('attendance.import');
    Route::post('/attendance/save',       [\App\Http\Controllers\AttendanceController::class, 'save'])->name('attendance.save');

    Route::get('/performance/kpi',        [\App\Http\Controllers\PerformanceController::class, 'kpiAppraisal'])->name('performance.kpi');
    Route::get('/performance/attitude',   [\App\Http\Controllers\PerformanceController::class, 'attitude'])->name('performance.attitude');
    Route::get('/performance/report',     [\App\Http\Controllers\PerformanceController::class, 'report'])->name('performance.report');

    /*
    |--------------------------------------------------------------------------
    | MY DEPARTMENT KPI
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/my-department-kpi',
        [KpiController::class, 'myDepartmentKpi']
    )->name('kpi.my-department-kpi');

    Route::post(
        '/kpi/apply-template',
        [KpiController::class, 'applyTemplate']
    )->name('kpi.apply-template');

    /*
    |--------------------------------------------------------------------------
    | KPI TEMPLATE CRUD
    |--------------------------------------------------------------------------
    */

    Route::get('/kpi-templates',          [KpiTemplateController::class, 'index'])->name('kpi-templates.index');
    Route::post('/kpi-templates',         [KpiTemplateController::class, 'store'])->name('kpi-templates.store');
    Route::delete('/kpi-templates/{id}',  [KpiTemplateController::class, 'destroy'])->name('kpi-templates.destroy');

    /*
    |--------------------------------------------------------------------------
    | KPI LINKAGES (cascading targets)
    |--------------------------------------------------------------------------
    */

    Route::post('/linkages', [\App\Http\Controllers\LinkageController::class, 'store'])->name('linkage.store');
    Route::delete('/linkages/{id}', [\App\Http\Controllers\LinkageController::class, 'destroy'])->name('linkage.destroy');

    /*
    |--------------------------------------------------------------------------
    | AI
    |--------------------------------------------------------------------------
    */

    Route::post('/ai/chat', [AiController::class, 'chat'])
        ->name('ai.chat');

    Route::post('/ai/suggest-description', [AiController::class, 'suggestDescription'])
        ->name('ai.suggest-description');

    Route::post('/ai/score-description', [AiController::class, 'scoreDescription'])
        ->name('ai.score-description');

    Route::post('/ai/suggest-targets', [AiController::class, 'suggestTargets'])
        ->name('ai.suggest-targets');

});
