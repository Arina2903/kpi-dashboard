<?php

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
