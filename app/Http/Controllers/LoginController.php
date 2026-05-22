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
            'select' => '*',
            'order' => 'company_code.asc',
        ]);

        if (empty($employees)) {
            return back()->with('error', 'Email tidak dijumpai atau akaun tidak aktif.');
        }

        if (count($employees) > 1) {
            session([
                'available_dashboards' => $employees,
            ]);

            return redirect()->route('dashboard.choose');
        }

        $employee = $employees[0];

        $this->setEmployeeSession($employee);

        return redirect()->route('dashboard');
    }

    public function chooseDashboard(SupabaseService $supabase)
    {
        $dashboards = session('available_dashboards');

        if (!$dashboards) {
            return redirect()
                ->route('login')
                ->with('error', 'Sila login semula.');
        }

        foreach ($dashboards as &$dashboard) {
            $company = $supabase->get('companies', [
                'code' => 'eq.' . $dashboard['company_code'],
                'select' => 'name,display_name',
                'limit' => 1,
            ]);

            $dashboard['company_name'] =
                $company[0]['display_name']
                ?? $company[0]['name']
                ?? $dashboard['company_code'];

            $dashboard['employee_uuid'] = $dashboard['id'];
        }

        return view('choose-dashboard', [
            'dashboards' => $dashboards,
        ]);
    }

    public function selectDashboard(Request $request, SupabaseService $supabase)
    {
        $request->validate([
            'employee_uuid' => 'required|string',
            'company_code' => 'required|string',
        ]);

        $employees = $supabase->get('employees', [
            'id' => 'eq.' . $request->employee_uuid,
            'company_code' => 'eq.' . $request->company_code,
            'is_active' => 'eq.true',
            'select' => '*',
            'limit' => 1,
        ]);

        $employee = $employees[0] ?? null;

        if (!$employee) {
            return back()->with('error', 'Dashboard access tidak sah.');
        }

        session()->forget('available_dashboards');

        $this->setEmployeeSession($employee);

        return redirect()->route('dashboard');
    }

    private function setEmployeeSession(array $employee): void
    {
        session([
            'employee_uuid' => $employee['id'],
            'employee_id' => $employee['employee_id'],
            'employee_name' => $employee['short_name'] ?? $employee['full_name'] ?? 'User',
            'short_name' => $employee['short_name'] ?? null,
            'full_name' => $employee['full_name'] ?? null,
            'employee_role' => $employee['role'],
            'role' => $employee['role'],
            'position' => $employee['position'] ?? null,
            'department_code' => $employee['department_code'],
            'company_code' => $employee['company_code'] ?? 'RCG',
        ]);
    }

    public function logout()
    {
        session()->flush();

        return redirect()->route('login');
    }
}
