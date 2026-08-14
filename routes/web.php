<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\KpiTemplateController;
use App\Http\Controllers\TitanKpiController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('platform.login');
});

// Telegram Mini App shell — opened inside Telegram's WebView, no Laravel session
// is available there, so this stays outside the kpi.auth group. Auth for the
// data it loads happens per-request via Telegram initData (see routes/api.php).
Route::view('/telegram/app', 'telegram.app', [
    'botUsername' => env('TELEGRAM_BOT_USERNAME', ''),
])->name('telegram.app');

// The legacy employee-based login below assumes a `users` table with
// `password_hash`/`is_active` columns and an `employees` table for session
// setup afterward -- neither exists on this company's database (confirmed
// live: the Platform's `users` table is Supabase-Auth-linked instead, and
// `employees` doesn't exist at all). GET redirects to the working Platform
// login so nobody lands on a form that can only fail; POST is left in place
// only so a stale bookmark POSTing directly here still gets a clean 4xx/5xx
// from Supabase rather than this route not existing at all.
Route::get('/login', function () {
    return redirect()->route('platform.login');
})->name('login');

Route::post('/login', [AuthController::class, 'submitLogin'])
    ->name('login.submit');

/*
|--------------------------------------------------------------------------
| MULTI-COMPANY PLATFORM (Supabase Auth + RLS — separate from the legacy
| employee/company session-based auth above)
|--------------------------------------------------------------------------
*/

Route::get('/platform/login', [\App\Http\Controllers\Platform\AuthController::class, 'showLogin'])
    ->name('platform.login');

Route::post('/platform/login', [\App\Http\Controllers\Platform\AuthController::class, 'submitLogin'])
    ->name('platform.login.submit');

Route::post('/platform/logout', [\App\Http\Controllers\Platform\AuthController::class, 'logout'])
    ->name('platform.logout');

Route::get('/platform/invite/accept', [\App\Http\Controllers\Platform\InviteController::class, 'accept'])
    ->name('platform.invite.accept');

Route::post('/platform/invite/set-password', [\App\Http\Controllers\Platform\InviteController::class, 'setPassword'])
    ->name('platform.invite.set-password');

Route::middleware(['platform.auth'])->prefix('platform')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Platform\DashboardController::class, 'index'])
        ->name('platform.dashboard');

    Route::get('/companies', [\App\Http\Controllers\Platform\CompanyController::class, 'index'])
        ->name('platform.companies.index');

    Route::post('/companies', [\App\Http\Controllers\Platform\CompanyController::class, 'store'])
        ->name('platform.companies.store');

    Route::post('/companies/{company}/admins', [\App\Http\Controllers\Platform\CompanyController::class, 'storeAdmin'])
        ->name('platform.companies.admins.store');

    Route::get('/companies/{company}/departments', [\App\Http\Controllers\Platform\DepartmentController::class, 'index'])
        ->name('platform.departments.index');

    Route::post('/companies/{company}/departments', [\App\Http\Controllers\Platform\DepartmentController::class, 'store'])
        ->name('platform.departments.store');

    Route::post('/companies/{company}/departments/{department}/users', [\App\Http\Controllers\Platform\DepartmentController::class, 'storeUser'])
        ->name('platform.departments.users.store');

    Route::post('/companies/{company}/departments/{department}/roles', [\App\Http\Controllers\Platform\RoleController::class, 'store'])
        ->name('platform.roles.store');

    Route::delete('/companies/{company}/departments/{department}/roles/{role}', [\App\Http\Controllers\Platform\RoleController::class, 'destroy'])
        ->name('platform.roles.destroy');

    Route::get('/companies/{company}/kpis', [\App\Http\Controllers\Platform\KpiController::class, 'index'])
        ->name('platform.kpis.index');

    Route::post('/companies/{company}/kpi-categories', [\App\Http\Controllers\Platform\KpiController::class, 'storeCategory'])
        ->name('platform.kpi-categories.store');

    Route::post('/companies/{company}/kpis', [\App\Http\Controllers\Platform\KpiController::class, 'store'])
        ->name('platform.kpis.store');

    Route::get('/companies/{company}/departments/{department}/submissions', [\App\Http\Controllers\Platform\KpiSubmissionController::class, 'index'])
        ->name('platform.submissions.index');

    Route::post('/companies/{company}/departments/{department}/submissions', [\App\Http\Controllers\Platform\KpiSubmissionController::class, 'store'])
        ->name('platform.submissions.store');
});

/*
|--------------------------------------------------------------------------
| FORGOT / RESET PASSWORD
|--------------------------------------------------------------------------
*/

Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])
    ->name('password.forgot');

Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
    ->name('password.forgot.submit');

Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])
    ->name('password.reset');

Route::post('/reset-password', [AuthController::class, 'submitResetPassword'])
    ->name('password.reset.submit');

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

    // SLT Office only — staff KPI drill-down
    Route::get('/dashboard/staff/{employeeId}', [DashboardController::class, 'staffKpis'])
        ->name('dashboard.staff.kpis');

    Route::get('/dashboard/staff/{employeeId}/kpi/{kpiId}', [DashboardController::class, 'staffKpiDetail'])
        ->name('dashboard.staff.kpi.detail');

    // SLT Office / BTS only — company-wide quarterly appraisal summary
    Route::get('/slt-dashboard', [\App\Http\Controllers\SltDashboardController::class, 'index'])
        ->name('slt-dashboard');

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
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile');
    Route::get('/settings', [\App\Http\Controllers\ProfileController::class, 'settings'])->name('settings');
    Route::post('/settings/telegram/connect', [\App\Http\Controllers\ProfileController::class, 'connectTelegram'])->name('settings.telegram.connect');
    Route::get('/settings/telegram/status', [\App\Http\Controllers\ProfileController::class, 'telegramStatus'])->name('settings.telegram.status');
    Route::post('/settings/email', [\App\Http\Controllers\ProfileController::class, 'updateEmail'])->name('settings.email.update');
    Route::post('/settings/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/settings/theme', [\App\Http\Controllers\ProfileController::class, 'updateTheme'])->name('settings.theme.update');
    Route::post('/settings/salutation', [\App\Http\Controllers\ProfileController::class, 'updateSalutation'])->name('settings.salutation.update');

    /*
    |--------------------------------------------------------------------------
    | JOB DESCRIPTION
    |--------------------------------------------------------------------------
    */

    Route::get('/job-description', [\App\Http\Controllers\JobDescriptionController::class, 'index'])->name('job-description');
    Route::post('/job-description', [\App\Http\Controllers\JobDescriptionController::class, 'update'])->name('job-description.update');

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    /*
    |--------------------------------------------------------------------------
    | HELP
    |--------------------------------------------------------------------------
    */

    Route::get('/help', [\App\Http\Controllers\HelpController::class, 'index'])->name('help');

    /*
    |--------------------------------------------------------------------------
    | PERFORMANCE EVALUATION
    |--------------------------------------------------------------------------
    */
    Route::get('/attendance',             [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/import',      fn() => redirect()->route('attendance.index')); // GET fallback — prevents 405 on refresh/back
    Route::post('/attendance/import',     [\App\Http\Controllers\AttendanceController::class, 'import'])->name('attendance.import');
    Route::post('/attendance/save',       [\App\Http\Controllers\AttendanceController::class, 'save'])->name('attendance.save');

    Route::get('/performance/kpi',                          [\App\Http\Controllers\PerformanceController::class, 'kpiAppraisal'])->name('performance.kpi');
    Route::get('/performance/attitude',                     [\App\Http\Controllers\PerformanceController::class, 'attitude'])->name('performance.attitude');
    Route::get('/performance/report',                       fn() => redirect('/performance/report/q2'))->name('performance.report');
    Route::get('/performance/report/{quarter}',             [\App\Http\Controllers\PerformanceController::class, 'reportQuarter'])->middleware('no-cache')->name('performance.report.quarter');
    Route::post('/performance/report/{quarter}/save',       [\App\Http\Controllers\PerformanceController::class, 'saveReport'])->name('performance.report.save');
    Route::get('/performance/appraise/{employeeId}/{quarter}', [\App\Http\Controllers\PerformanceController::class, 'appraiserReport'])->middleware('no-cache')->name('performance.appraise.report');
    Route::post('/performance/appraise/{employeeId}/{quarter}/save', [\App\Http\Controllers\PerformanceController::class, 'appraiserSave'])->name('performance.appraise.save');
    Route::get('/performance/appraise/{employeeId}/kpi/{kpiId}', [\App\Http\Controllers\PerformanceController::class, 'viewAppraiseeKpi'])->middleware('no-cache')->name('performance.appraise.kpi');

    /*
    |--------------------------------------------------------------------------
    | MINI APP — web version of the Telegram Mini App, same session auth as
    | the rest of the dashboard (see MiniAppController / MiniAppTaskController)
    |--------------------------------------------------------------------------
    */
    Route::get('/mini-app', [\App\Http\Controllers\MiniAppController::class, 'index'])->name('mini-app');

    Route::middleware('telegram.linked')->group(function () {
        Route::get('/mini-app/api/kpis/open', [\App\Http\Controllers\MiniAppController::class, 'openKpis'])->name('mini-app.kpis.open');
        Route::get('/mini-app/api/kpis/summary', [\App\Http\Controllers\MiniAppController::class, 'summary'])->name('mini-app.kpis.summary');
        Route::post('/mini-app/api/kpis/{kpiId}/quarters/{quarterId}/adjust', [\App\Http\Controllers\MiniAppController::class, 'adjustQuarter'])->name('mini-app.kpis.adjust');
        Route::get('/mini-app/api/reviews', [\App\Http\Controllers\MiniAppController::class, 'reviews'])->name('mini-app.reviews');

        Route::get('/mini-app/api/tasks', [\App\Http\Controllers\MiniAppTaskController::class, 'index'])->name('mini-app.tasks.index');
        Route::get('/mini-app/api/tasks/kpi-options', [\App\Http\Controllers\MiniAppTaskController::class, 'kpiOptions'])->name('mini-app.tasks.kpi-options');
        Route::get('/mini-app/api/tasks/score', [\App\Http\Controllers\PerformixInsightsController::class, 'myScore'])->name('mini-app.tasks.score');
        Route::post('/mini-app/api/tasks', [\App\Http\Controllers\MiniAppTaskController::class, 'store'])->name('mini-app.tasks.store');
        Route::get('/mini-app/api/tasks/{id}', [\App\Http\Controllers\MiniAppTaskController::class, 'show'])->name('mini-app.tasks.show');
        Route::patch('/mini-app/api/tasks/{id}', [\App\Http\Controllers\MiniAppTaskController::class, 'update'])->name('mini-app.tasks.update');
        Route::post('/mini-app/api/tasks/{id}/progress', [\App\Http\Controllers\MiniAppTaskController::class, 'progress'])->name('mini-app.tasks.progress');
        Route::post('/mini-app/api/tasks/{id}/daily-update', [\App\Http\Controllers\MiniAppTaskController::class, 'dailyUpdate'])->name('mini-app.tasks.daily-update');
        Route::post('/mini-app/api/tasks/{id}/kpi-suggestion', [\App\Http\Controllers\MiniAppTaskController::class, 'kpiSuggestion'])->name('mini-app.tasks.kpi-suggestion');
        Route::post('/mini-app/api/tasks/{id}/link-kpis', [\App\Http\Controllers\MiniAppTaskController::class, 'linkKpis'])->name('mini-app.tasks.link-kpis');
        Route::delete('/mini-app/api/tasks/{id}', [\App\Http\Controllers\MiniAppTaskController::class, 'destroy'])->name('mini-app.tasks.destroy');

        Route::get('/mini-app/api/team/attention', [\App\Http\Controllers\PerformixInsightsController::class, 'teamAttention'])->name('mini-app.team.attention');
        Route::get('/mini-app/api/summaries', [\App\Http\Controllers\PerformixInsightsController::class, 'summaries'])->name('mini-app.summaries.index');
        Route::post('/mini-app/api/summaries/regenerate', [\App\Http\Controllers\PerformixInsightsController::class, 'regenerate'])->name('mini-app.summaries.regenerate');
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
    | TITAN KPI DASHBOARD (RCG / TITAN dept only, no VP)
    |--------------------------------------------------------------------------
    */

    Route::get('/titan-kpi',              [TitanKpiController::class, 'index'])->name('titan-kpi.index');
    Route::post('/titan-kpi/sync',        [TitanKpiController::class, 'sync'])->name('titan-kpi.sync');
    Route::post('/titan-kpi/weightage',   [TitanKpiController::class, 'updateWeightage'])->name('titan-kpi.weightage');

    /*
    |--------------------------------------------------------------------------
    | KPI LINKAGES (cascading targets)
    |--------------------------------------------------------------------------
    */

    Route::get('/linkages', [\App\Http\Controllers\LinkageController::class, 'index'])->name('linkages');
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

    Route::post('/ai/score-quarter', [AiController::class, 'scoreQuarter'])
        ->name('ai.score-quarter');

    Route::post('/ai/suggest-kpi', [AiController::class, 'suggestKpi'])
        ->name('ai.suggest-kpi');

    /*
    |--------------------------------------------------------------------------
    | ADMIN — VIEW AS (BTS department only)
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/view-as', [\App\Http\Controllers\AdminController::class, 'index'])->name('admin.view-as');
    Route::post('/admin/view-as/stop', [\App\Http\Controllers\AdminController::class, 'stop'])->name('admin.view-as.stop');
    Route::post('/admin/view-as/{employeeId}', [\App\Http\Controllers\AdminController::class, 'start'])->name('admin.view-as.start');

});
