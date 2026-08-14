<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     *
     * This app has no Laravel auth guard/users table — access is session-based
     * (see KpiAuth middleware), so the gate checks the same session key
     * AdminController::ensureBts() uses for every other BTS-only admin area,
     * rather than Auth::user().
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function () {
            return strtoupper(trim(session('department_code') ?? '')) === 'BTS';
        });
    }
}
