<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request, SupabaseService $supabase)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $defaultPassword = env('DEFAULT_LOGIN_PASSWORD', 'Richworks');

        if ($request->password !== $defaultPassword) {
            return back()->with('error', 'Email atau password tidak betul.');
        }

        $employees = $supabase->get('employees', [
            'email' => 'eq.' . $request->email,
            'is_active' => 'eq.true',
            'select' => '*'
        ]);

        $employee = $employees[0] ?? null;

        if (!$employee) {
            return back()->with('error', 'Email tidak dijumpai atau akaun tidak aktif.');
        }

        session([
            'employee_uuid' => $employee['id'],
            'employee_id' => $employee['employee_id'],
            'employee_name' => $employee['short_name'] ?? $employee['full_name'] ?? 'User',
            'employee_role' => $employee['role'],
            'department_code' => $employee['department_code'],
            'company_code' => $employee['company_code'] ?? 'RCG',
        ]);

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        session()->flush();

        return redirect()->route('login');
    }
}
