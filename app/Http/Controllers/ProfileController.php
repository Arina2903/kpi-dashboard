<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    private function currentUser(SupabaseService $supabase): array
    {
        $employees = $supabase->get('employees', [
            'id'        => 'eq.' . session('employee_uuid'),
            'is_active' => 'eq.true',
            'select'    => '*',
        ]);

        if (empty($employees)) {
            session()->flush();
            abort(403, 'Employee not found.');
        }

        return $employees[0];
    }

    private function sidebarData(SupabaseService $supabase, array $user): array
    {
        $departments = $supabase->get('departments', [
            'company_code' => 'eq.' . $user['company_code'],
            'select'       => '*',
            'order'        => 'name.asc',
        ]) ?? [];

        $role                   = strtoupper(trim($user['role'] ?? ''));
        $canSwitchDepartment    = $role === 'SLT';
        $selectedDepartmentCode = session('selected_department_code') ?? $user['department_code'] ?? null;

        $department = null;
        if ($selectedDepartmentCode) {
            $res        = $supabase->get('departments', ['code' => 'eq.' . $selectedDepartmentCode, 'select' => '*']);
            $department = $res[0] ?? null;
        }

        $pendingApprovalCount = count($supabase->get('kpi_update_approvals', [
            'approver_id' => 'eq.' . $user['id'],
            'status'      => 'eq.pending',
            'select'      => 'id',
        ]) ?? []);

        return compact('departments', 'department', 'canSwitchDepartment', 'selectedDepartmentCode', 'pendingApprovalCount');
    }

    public function index(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);

        $manager = null;
        if (!empty($user['reports_to_id'])) {
            $res      = $supabase->get('employees', ['id' => 'eq.' . $user['reports_to_id'], 'select' => 'short_name,full_name,position']);
            $manager  = $res[0] ?? null;
        }

        return view('profile', array_merge([
            'user'    => $user,
            'manager' => $manager,
        ], $this->sidebarData($supabase, $user)));
    }

    public function connectTelegram(Request $request, SupabaseService $supabase)
    {
        $code = Str::upper(Str::random(8));

        $supabase->safePatch('users', ['id' => 'eq.' . session('user_uuid')], [
            'telegram_link_code' => $code,
            'telegram_link_code_expires_at' => now()->addMinutes(5)->toIso8601String(),
        ]);

        return response()->json([
            'code' => $code,
            'deep_link' => 'https://t.me/' . env('TELEGRAM_BOT_USERNAME') . '?start=' . $code,
        ]);
    }

    public function telegramStatus(Request $request, SupabaseService $supabase)
    {
        $user = $supabase->first('users', [
            'id' => 'eq.' . session('user_uuid'),
            'select' => 'telegram_username,telegram_linked_at',
        ]);

        return response()->json([
            'linked' => !empty($user['telegram_linked_at']),
            'username' => $user['telegram_username'] ?? null,
            'linked_at' => $user['telegram_linked_at'] ?? null,
        ]);
    }
}
