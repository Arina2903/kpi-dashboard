<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class KpiAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('employee_uuid')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
