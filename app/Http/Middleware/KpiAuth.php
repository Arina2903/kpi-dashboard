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
        | APPEARANCE THEME + DISPLAY TITLE (Account Settings)
        |--------------------------------------------------------------------------
        | Fetched once per session and cached as flat session keys so every page
        | (via partials/sidebar.blade.php) can read it without its own query.
        | ProfileController::updateTheme()/updateSalutation() overwrite these same
        | keys immediately on save, so a change takes effect without needing to
        | log out/in — but salutation set/cleared any OTHER way (direct DB edit,
        | admin action) only reaches sessions created before that change once
        | they refresh here, since login is the only other place it's loaded.
        */

        if (!session()->has('settings_synced_v2')) {
            try {
                $employee = app(SupabaseService::class)->first('employees', [
                    'id'     => 'eq.' . session('employee_uuid'),
                    'select' => 'salutation,theme_bg,theme_card,theme_accent,theme_accent2,theme_border,theme_text,theme_sidebar_bg,theme_sidebar_accent,theme_sidebar_text,theme_font_family,theme_font_size',
                ]);

                session([
                    'settings_synced_v2'          => true,
                    'salutation'            => $employee['salutation']            ?? null,
                    'theme_bg'              => $employee['theme_bg']              ?? null,
                    'theme_card'            => $employee['theme_card']            ?? null,
                    'theme_accent'          => $employee['theme_accent']          ?? null,
                    'theme_accent2'         => $employee['theme_accent2']         ?? null,
                    'theme_border'          => $employee['theme_border']          ?? null,
                    'theme_text'            => $employee['theme_text']            ?? null,
                    'theme_sidebar_bg'      => $employee['theme_sidebar_bg']      ?? null,
                    'theme_sidebar_accent'  => $employee['theme_sidebar_accent']  ?? null,
                    'theme_sidebar_text'    => $employee['theme_sidebar_text']    ?? null,
                    'theme_font_family'     => $employee['theme_font_family']     ?? null,
                    'theme_font_size'       => $employee['theme_font_size']       ?? null,
                ]);
            } catch (\Throwable) {
                session(['settings_synced_v2' => true]);
            }
        }

        return $next($request);
    }
}
