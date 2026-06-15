<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;

class ActivityLogController extends Controller
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

        $role                 = strtoupper(trim($user['role'] ?? ''));
        $canSwitchDepartment  = $role === 'SLT';
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
        $user        = $this->currentUser($supabase);
        $role        = strtoupper(trim($user['role'] ?? ''));
        $companyCode = $user['company_code'];
        $fy          = 'FY' . now()->year;

        // ── Determine which KPIs this user can see ─────────────────────────
        $kpiFilters = [
            'company_code'   => 'eq.' . $companyCode,
            'financial_year' => 'eq.' . $fy,
            'select'         => 'id,kpi_title,employee_id',
            'order'          => 'created_at.desc',
        ];

        if ($role === 'EXECUTIVE') {
            $kpiFilters['employee_id'] = 'eq.' . $user['id'];
        } elseif ($role === 'MANAGER' || $role === 'VP') {
            $kpiFilters['department_code'] = 'eq.' . $user['department_code'];
        }
        // SLT sees all KPIs in the company

        $visibleKpis = $supabase->get('kpis', $kpiFilters) ?? [];
        $kpiMap      = collect($visibleKpis)->keyBy('id');
        $kpiIds      = array_column($visibleKpis, 'id');

        // ── Fetch employee names for KPI owners ────────────────────────────
        $empIds = array_unique(array_filter(array_column($visibleKpis, 'employee_id')));
        $empMap = [];
        if (!empty($empIds)) {
            $emps = $supabase->get('employees', [
                'id'     => 'in.(' . implode(',', $empIds) . ')',
                'select' => 'id,full_name,short_name',
            ]) ?? [];
            foreach ($emps as $e) {
                $empMap[$e['id']] = $e['short_name'] ?? $e['full_name'] ?? '-';
            }
        }

        $logs = collect();

        // ── KPI Created ────────────────────────────────────────────────────
        foreach ($visibleKpis as $k) {
            $kpiCreationFilters = [
                'company_code'   => 'eq.' . $companyCode,
                'financial_year' => 'eq.' . $fy,
                'id'             => 'eq.' . $k['id'],
                'select'         => 'id,kpi_title,created_at,employee_id',
            ];
            // We already have the data in $visibleKpis, but need created_at
        }
        // Re-fetch with created_at
        $kpiCreations = $supabase->get('kpis', array_merge(
            $kpiFilters,
            ['select' => 'id,kpi_title,created_at,employee_id']
        )) ?? [];

        foreach ($kpiCreations as $k) {
            $logs->push([
                'type'      => 'kpi_created',
                'label'     => 'KPI Created',
                'color'     => 'blue',
                'who'       => $empMap[$k['employee_id']] ?? '-',
                'kpi_title' => $k['kpi_title'] ?? '-',
                'detail'    => 'New KPI added for ' . $fy,
                'at'        => $k['created_at'] ?? '',
            ]);
        }

        if (!empty($kpiIds)) {
            $inFilter = 'in.(' . implode(',', $kpiIds) . ')';

            // ── KPI Field Edits ────────────────────────────────────────────
            $histories = $supabase->get('kpi_histories', [
                'kpi_id' => $inFilter,
                'select' => '*',
                'order'  => 'created_at.desc',
                'limit'  => '300',
            ]) ?? [];

            foreach ($histories as $h) {
                $kpi = $kpiMap->get($h['kpi_id']);
                $fieldLabel = ucwords(str_replace('_', ' ', $h['field_name'] ?? ''));
                $logs->push([
                    'type'      => 'kpi_edited',
                    'label'     => 'KPI Edited',
                    'color'     => 'indigo',
                    'who'       => $h['edited_by_name'] ?? '-',
                    'kpi_title' => $kpi['kpi_title'] ?? '-',
                    'detail'    => $fieldLabel . ': "' . ($h['old_value'] ?? '-') . '" → "' . ($h['new_value'] ?? '-') . '"',
                    'at'        => $h['created_at'] ?? '',
                ]);
            }

            // ── Quarter Update Approvals ───────────────────────────────────
            $approvals = $supabase->get('kpi_update_approvals', [
                'kpi_id' => $inFilter,
                'select' => '*',
                'order'  => 'created_at.desc',
                'limit'  => '300',
            ]) ?? [];

            foreach ($approvals as $a) {
                $kpi = $kpiMap->get($a['kpi_id']);

                $logs->push([
                    'type'      => 'update_submitted',
                    'label'     => 'Update Submitted',
                    'color'     => 'amber',
                    'who'       => $a['requested_by_name'] ?? '-',
                    'kpi_title' => $kpi['kpi_title'] ?? '-',
                    'detail'    => ($a['quarter'] ?? '') . ' — Actual: ' . number_format((float)($a['requested_actual'] ?? 0), 1),
                    'at'        => $a['created_at'] ?? '',
                ]);

                if (!empty($a['approved_at'])) {
                    $logs->push([
                        'type'      => 'update_approved',
                        'label'     => 'Update Approved',
                        'color'     => 'green',
                        'who'       => $a['approved_by_name'] ?? $a['approver_name'] ?? '-',
                        'kpi_title' => $kpi['kpi_title'] ?? '-',
                        'detail'    => ($a['quarter'] ?? '') . ' approved' . ($a['approver_remark'] ? ' — ' . $a['approver_remark'] : ''),
                        'at'        => $a['approved_at'],
                    ]);
                }

                if (!empty($a['rejected_at'])) {
                    $logs->push([
                        'type'      => 'update_rejected',
                        'label'     => 'Update Rejected',
                        'color'     => 'red',
                        'who'       => $a['rejected_by_name'] ?? $a['approver_name'] ?? '-',
                        'kpi_title' => $kpi['kpi_title'] ?? '-',
                        'detail'    => ($a['quarter'] ?? '') . ' rejected' . ($a['rejection_reason'] ? ' — ' . $a['rejection_reason'] : ''),
                        'at'        => $a['rejected_at'],
                    ]);
                }
            }

            // ── Quarter Completion Submissions ─────────────────────────────
            $quarters = $supabase->get('kpi_quarters', [
                'kpi_id'                   => $inFilter,
                'completion_submitted_at'  => 'not.is.null',
                'select'                   => 'id,kpi_id,quarter,status,completion_submitted_at,completion_submitted_by',
                'order'                    => 'completion_submitted_at.desc',
                'limit'                    => '200',
            ]) ?? [];

            $submitterIds = array_unique(array_filter(array_column($quarters, 'completion_submitted_by')));
            $submitterMap = [];
            if (!empty($submitterIds)) {
                $submitters = $supabase->get('employees', [
                    'id'     => 'in.(' . implode(',', $submitterIds) . ')',
                    'select' => 'id,full_name,short_name',
                ]) ?? [];
                foreach ($submitters as $s) {
                    $submitterMap[$s['id']] = $s['short_name'] ?? $s['full_name'] ?? '-';
                }
            }

            foreach ($quarters as $q) {
                $kpi = $kpiMap->get($q['kpi_id']);
                $logs->push([
                    'type'      => 'completion_submitted',
                    'label'     => 'Completion Submitted',
                    'color'     => 'purple',
                    'who'       => $submitterMap[$q['completion_submitted_by'] ?? ''] ?? '-',
                    'kpi_title' => $kpi['kpi_title'] ?? '-',
                    'detail'    => ($q['quarter'] ?? '') . ' — quarter completion submitted for review',
                    'at'        => $q['completion_submitted_at'] ?? '',
                ]);
            }

            // ── KPI Delete Requests ────────────────────────────────────────
            $deleteReqs = $supabase->get('kpi_delete_requests', [
                'kpi_id' => $inFilter,
                'select' => '*',
                'order'  => 'created_at.desc',
                'limit'  => '100',
            ]) ?? [];

            foreach ($deleteReqs as $d) {
                $kpi = $kpiMap->get($d['kpi_id']);
                $logs->push([
                    'type'      => 'delete_requested',
                    'label'     => 'Delete Requested',
                    'color'     => 'rose',
                    'who'       => $d['requested_by_name'] ?? '-',
                    'kpi_title' => $kpi['kpi_title'] ?? '-',
                    'detail'    => 'Status: ' . ucfirst($d['status'] ?? 'pending') . ($d['reason'] ? ' — ' . $d['reason'] : ''),
                    'at'        => $d['created_at'] ?? '',
                ]);
            }
        }

        // ── Filter & sort ──────────────────────────────────────────────────
        $typeFilter = $request->get('type', '');
        if ($typeFilter) {
            $logs = $logs->filter(fn($l) => $l['type'] === $typeFilter);
        }

        $logs = $logs->filter(fn($l) => !empty($l['at']))->sortByDesc('at')->values();

        return view('kpi.activity-log', array_merge([
            'user'       => $user,
            'logs'       => $logs,
            'typeFilter' => $typeFilter,
            'fy'         => $fy,
        ], $this->sidebarData($supabase, $user)));
    }
}
