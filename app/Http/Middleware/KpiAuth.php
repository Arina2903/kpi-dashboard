<?php

namespace App\Http\Middleware;

use App\Services\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KpiAuth
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        /*
        |--------------------------------------------------------------------------
        | SIMPLE LOGIN CHECK
        |--------------------------------------------------------------------------
        */

        if(
            !session()->has('employee_uuid')
        ){

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Please login terlebih dahulu.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | OPTIONAL AUTO FIX
        |--------------------------------------------------------------------------
        */

        if(
            !session()->has('employee')
        ){

            session([
                'employee' => [
                    'id' => session('employee_uuid'),
                    'role' => session('role'),
                    'short_name' => session('short_name'),
                    'department_code' => session('department_code'),
                    'company_code' => session('company_code'),
                ]
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | APPEARANCE THEME (Account Settings > Appearance)
        |--------------------------------------------------------------------------
        | Fetched once per session and cached as flat session keys so every page
        | (via partials/sidebar.blade.php) can read it without its own query.
        | ProfileController::updateTheme() overwrites these same keys immediately
        | on save, so a change takes effect without needing to log out/in.
        */

        if (!session()->has('theme_loaded')) {
            try {
                $employee = app(SupabaseService::class)->first('employees', [
                    'id'     => 'eq.' . session('employee_uuid'),
                    'select' => 'theme_bg,theme_card,theme_accent,theme_border',
                ]);

                session([
                    'theme_loaded' => true,
                    'theme_bg'     => $employee['theme_bg']     ?? null,
                    'theme_card'   => $employee['theme_card']   ?? null,
                    'theme_accent' => $employee['theme_accent'] ?? null,
                    'theme_border' => $employee['theme_border'] ?? null,
                ]);
            } catch (\Throwable) {
                session(['theme_loaded' => true]);
            }
        }

        return $next($request);
    }
}
