<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;

class JobDescriptionController extends Controller
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

        $jobDescription = $supabase->first('job_descriptions', [
            'employee_id' => 'eq.' . $user['id'],
            'select'      => '*',
        ]);

        $manager = null;
        if (!empty($user['reports_to_id'])) {
            $res     = $supabase->get('employees', ['id' => 'eq.' . $user['reports_to_id'], 'select' => 'short_name,full_name,position']);
            $manager = $res[0] ?? null;
        }

        return view('job-description', array_merge([
            'user'            => $user,
            'manager'         => $manager,
            'jobDescription'  => $jobDescription,
        ], $this->sidebarData($supabase, $user)));
    }

    private function linesToArray(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->all();
    }

    public function update(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);

        $supabase->upsert('job_descriptions', [
            'employee_id'      => $user['id'],
            'summary'          => trim((string) $request->input('summary', '')),
            'responsibilities' => $this->linesToArray($request->input('responsibilities')),
            'requirements'     => $this->linesToArray($request->input('requirements')),
            'competencies'     => $this->linesToArray($request->input('competencies')),
            'updated_at'       => now()->toIso8601String(),
        ], 'employee_id');

        return redirect()->route('job-description')->with('success', 'Job description updated.');
    }
}
