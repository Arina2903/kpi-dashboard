<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Services\ApprovalHierarchyService;
use App\Services\SupabaseService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ApprovalHierarchyService::class,
            function ($app) {

                return new ApprovalHierarchyService(
                    $app->make(
                        SupabaseService::class
                    )
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (
            app()->environment('production')
        ) {
            URL::forceScheme('https');
        }

        // Unread notification count for the sidebar bell — computed here via a
        // composer so every page that includes the sidebar gets it, instead of
        // every controller having to remember to pass it (see how spotty
        // $pendingApprovalCount is by comparison).
        View::composer('partials.sidebar', function ($view) {
            $unreadCount = 0;
            $employeeId  = session('employee_uuid');

            if ($employeeId) {
                try {
                    $rows = $this->app->make(SupabaseService::class)->get('notifications', [
                        'recipient_employee_id' => 'eq.' . $employeeId,
                        'is_read'                => 'eq.false',
                        'select'                 => 'id',
                    ]) ?? [];
                    $unreadCount = count($rows);
                } catch (\Throwable $e) {
                    // Sidebar must never break the whole page over a notification query.
                }
            }

            $view->with('unreadNotificationCount', $unreadCount);
        });
    }
}
