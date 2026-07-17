<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;

class NotificationController extends Controller
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

    public function index(SupabaseService $supabase)
    {
        if (!session()->has('employee_uuid') || !session()->has('company_code')) {
            return redirect()->route('login')->with('error', 'Sila login terlebih dahulu.');
        }

        $user = $this->currentUser($supabase);

        $notifications = $supabase->get('notifications', [
            'recipient_employee_id' => 'eq.' . $user['id'],
            'select'                => '*',
            'order'                 => 'created_at.desc',
            'limit'                 => 100,
        ]) ?? [];

        return view('notifications', array_merge([
            'user'          => $user,
            'notifications' => $notifications,
        ], $this->sidebarData($supabase, $user)));
    }

    public function markRead(string $id, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);

        $supabase->update('notifications', [
            'id'                     => 'eq.' . $id,
            'recipient_employee_id'  => 'eq.' . $user['id'],
        ], ['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllRead(SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);

        $supabase->update('notifications', [
            'recipient_employee_id' => 'eq.' . $user['id'],
            'is_read'               => 'eq.false',
        ], ['is_read' => true]);

        return redirect()->route('notifications');
    }
}
