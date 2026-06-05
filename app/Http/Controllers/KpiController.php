<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\SupabaseService;
use App\Services\ApprovalHierarchyService;

class KpiController extends Controller
{
    protected $supabase;
    protected ApprovalHierarchyService $hierarchyService;

    public function __construct(
        SupabaseService $supabase,
        ApprovalHierarchyService $hierarchyService
    ){
        $this->supabase = $supabase;
        $this->hierarchyService = $hierarchyService;
    }

    private function currentFY(): string
    {
        return 'FY' . now()->year;
    }

    private function nowMy(): string
    {
        return now()->timezone('Asia/Kuala_Lumpur')->toDateTimeString();
    }

    private function currentUser(SupabaseService $supabase): array
    {
        $employeeUuid = session('employee_uuid');

        if (!$employeeUuid) {
            abort(403, 'Employee not logged in.');
        }

        $employees = $supabase->get('employees', [
            'id' => 'eq.' . $employeeUuid,
            'is_active' => 'eq.true',
            'select' => '*',
        ]);

        if (empty($employees)) {
            session()->flush();
            abort(403, 'Employee not found.');
        }

        return $employees[0];
    }

    private function permissionForRoleFromDb(SupabaseService $supabase, string $role): array
    {
        $permissions = $supabase->get('kpi_permissions', [
            'role' => 'eq.' . $role,
            'select' => '*',
        ]);

        if (empty($permissions)) {
            abort(403, 'KPI permission not configured for role: ' . $role);
        }

        return $permissions[0];
    }

    private function sidebarData(SupabaseService $supabase, array $user): array
    {
        $departmentFilters = [
            'select' => '*',
            'order' => 'name.asc',
        ];

        if (!empty($user['company_code'])) {
            $departmentFilters['company_code'] = 'eq.' . $user['company_code'];
        }

        $departments = $supabase->get('departments', $departmentFilters) ?? [];

        $role = strtoupper(trim($user['role'] ?? ''));

        $canSwitchDepartment = in_array($role, [
            'ADMIN', 'SLT', 'CCO', 'CCMO', 'VP',
        ]);

        $selectedDepartmentCode = session('selected_department_code')
            ?? $user['department_code']
            ?? null;

        $department = null;

        if ($selectedDepartmentCode) {
            $departmentResult = $supabase->get('departments', [
                'code' => 'eq.' . $selectedDepartmentCode,
                'select' => '*',
            ]);

            $department = $departmentResult[0] ?? null;
        }

        return [
            'departments' => $departments,
            'department' => $department,
            'canSwitchDepartment' => $canSwitchDepartment,
            'selectedDepartmentCode' => $selectedDepartmentCode,
        ];
    }

    public function index(SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        $permission = $this->permissionForRoleFromDb($supabase, $user['role']);
        $fy = $this->currentFY();

        $role = strtoupper(trim($user['role'] ?? ''));

        $employees = [];
        $kpis = [];

        if (in_array($role, ['ADMIN', 'SLT', 'CCO', 'CCMO', 'VP'])) {
            $selectedDepartmentCode = session('selected_department_code')
                ?? $user['department_code'];

            $kpis = $supabase->get('kpis', [
                'company_code' => 'eq.' . $user['company_code'],
                'department_code' => 'eq.' . $selectedDepartmentCode,
                'financial_year' => 'eq.' . $fy,
                'select' => '*',
                'order' => 'created_at.desc',
            ]) ?? [];

            $employees = $supabase->get('employees', [
                'company_code' => 'eq.' . $user['company_code'],
                'department_code' => 'eq.' . $selectedDepartmentCode,
                'is_active' => 'eq.true',
                'select' => 'id,employee_id,short_name,role,department_code',
            ]) ?? [];
        } elseif ($role === 'MANAGER') {
            $kpis = $supabase->get('kpis', [
                'company_code' => 'eq.' . $user['company_code'],
                'department_code' => 'eq.' . $user['department_code'],
                'financial_year' => 'eq.' . $fy,
                'select' => '*',
                'order' => 'created_at.desc',
            ]) ?? [];

            $employees = $supabase->get('employees', [
                'company_code' => 'eq.' . $user['company_code'],
                'department_code' => 'eq.' . $user['department_code'],
                'is_active' => 'eq.true',
                'select' => 'id,employee_id,short_name,role,department_code',
            ]) ?? [];
        } else {

        /*
        |--------------------------------------------------------------------------
        | OWN KPI
        |--------------------------------------------------------------------------
        */

        $ownKpis = $supabase->get('kpis', [

            'employee_id' => 'eq.' . $user['id'],

            'financial_year' => 'eq.' . $fy,

            'select' => '*',

            'order' => 'created_at.desc',

        ]) ?? [];

        /*
        |--------------------------------------------------------------------------
        | ASSIGNED KPI IDS
        |--------------------------------------------------------------------------
        */

        $assignments = $supabase->get('kpi_assignments', [

            'assigned_employee_id' => 'eq.' . $user['id'],

            'select' => '*',

        ]) ?? [];

        $assignedKpiIds = collect($assignments)

            ->pluck('kpi_id')

            ->filter()

            ->unique()

            ->values()

            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | ASSIGNED KPI
        |--------------------------------------------------------------------------
        */

        $assignedKpis = [];

        if (!empty($assignedKpiIds)) {

            $assignedKpis = $supabase->get('kpis', [

                'id' => 'in.(' . implode(',', $assignedKpiIds) . ')',

                'financial_year' => 'eq.' . $fy,

                'select' => '*',

                'order' => 'created_at.desc',

            ]) ?? [];
        }

        /*
        |--------------------------------------------------------------------------
        | MARK ASSIGNED KPI
        |--------------------------------------------------------------------------
        */

        $assignedKpis = collect($assignedKpis)

            ->map(function ($kpi) {

                $kpi['is_assigned_kpi'] = true;

                return $kpi;

            })

            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | MARK OWN KPI
        |--------------------------------------------------------------------------
        */

        $ownKpis = collect($ownKpis)

            ->map(function ($kpi) {

                $kpi['is_assigned_kpi'] = false;

                return $kpi;

            })

            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | MERGE KPI
        |--------------------------------------------------------------------------
        */

        $kpis = collect([

            ...$ownKpis,

            ...$assignedKpis,

        ])

        /*
        |--------------------------------------------------------------------------
        | REMOVE DUPLICATE KPI
        |--------------------------------------------------------------------------
        */

        ->unique('id')

        ->values()

        ->toArray();

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEES
        |--------------------------------------------------------------------------
        */

        $employeeIds = collect($kpis)

            ->pluck('employee_id')

            ->filter()

            ->unique()

            ->values()

            ->toArray();

        $employees = [];

        if(!empty($employeeIds)){

            $employees = $supabase->get('employees',[

                'id'
                    => 'in.('
                    . implode(',',$employeeIds)
                    . ')',

                'select'
                    => 'id,employee_id,short_name,role,department_code'

            ]) ?? [];
        }
    }

        $employeeMap = collect($employees)->keyBy('id');

        $creatorIds = collect($kpis)
            ->pluck('created_by')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $creators = [];

        if (!empty($creatorIds)) {
            $creators = $supabase->get('employees', [
                'id' => 'in.(' . implode(',', $creatorIds) . ')',
                'select' => 'id,employee_id,short_name,role,department_code',
            ]) ?? [];
        }

        $creatorMap = collect($creators)->keyBy('id');

        $kpis = collect($kpis)->map(function ($kpi) use ($employeeMap, $creatorMap, $supabase) {
            $employee = $employeeMap->get($kpi['employee_id']);
            $creator = $creatorMap->get($kpi['created_by'] ?? null);

            $kpi['employee_name'] = $employee['short_name'] ?? 'Unassigned';
            $kpi['employee_role'] = $employee['role'] ?? '-';
            $kpi['employee_code'] = $employee['employee_id'] ?? '-';
            $kpi['department_code'] = $kpi['department_code'] ?? ($employee['department_code'] ?? '-');

            $kpi['created_by_name'] = $creator['short_name'] ?? '-';
            $kpi['created_by_role'] = $creator['role'] ?? '-';

            $quarters = $supabase->get('kpi_quarters', [
                'kpi_id' => 'eq.' . $kpi['id'],
                'select' => '*',
                'order' => 'quarter.asc',
            ]) ?? [];

            $kpi['quarters'] = $quarters;
            $kpi['quarter_total_target'] = collect($quarters)->sum(fn ($q) => (float) ($q['quarter_target'] ?? 0));
            $quarterTotalActual = collect($quarters)
                ->sum(
                    fn ($q)
                    =>
                    (float)($q['quarter_actual'] ?? 0)
                );

            $kpi['quarter_total_actual']
                = $quarterTotalActual;

            $kpi['actual_value']
                = $quarterTotalActual;

            $kpi['quarter_overall_progress'] = $kpi['quarter_total_target'] > 0
                ? round(($kpi['quarter_total_actual'] / $kpi['quarter_total_target']) * 100, 2)
                : 0;

            $today = now('Asia/Kuala_Lumpur')->toDateString();

            $currentQuarter = collect($quarters)->first(function ($q) use ($today) {
                return !empty($q['start_date'])
                    && !empty($q['end_date'])
                    && $q['start_date'] <= $today
                    && $q['end_date'] >= $today;
            });

            $kpi['current_quarter'] = $currentQuarter['quarter'] ?? '-';
            $kpi['current_quarter_end_date'] = $currentQuarter['end_date'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | PENDING ACTUAL REQUEST
            |--------------------------------------------------------------------------
            */

            $pendingActual = $supabase->get(
                'kpi_update_approvals',
                [
                    'kpi_id' => 'eq.' . $kpi['id'],
                    'status' => 'eq.pending',
                    'select' => '*',
                    'order' => 'created_at.desc',
                    'limit' => 1,
                ]
            ) ?? [];

            $latestActualRequest =
                $pendingActual[0] ?? null;

            $kpi['has_pending_actual_request']
                = !empty($latestActualRequest);

            $kpi['actual_request_status']
                = $latestActualRequest['status']
                ?? null;

            /*
            |--------------------------------------------------------------------------
            | APPROVER INFO
            |--------------------------------------------------------------------------
            */

            $approver = null;

            if (
                !empty(
                    $latestActualRequest['approver_id']
                )
            ) {

                $approverResult = $supabase->get(
                    'employees',
                    [
                        'id'
                            => 'eq.' .
                            $latestActualRequest['approver_id'],

                        'select'
                            => 'id,short_name,role',

                        'limit'
                            => 1,
                    ]
                ) ?? [];

                $approver =
                    $approverResult[0] ?? null;
            }

            $kpi['approver_name']
                = $approver['short_name']
                ?? '-';

            $kpi['approver_role']
                = $approver['role']
                ?? '-';

            /*
            |--------------------------------------------------------------------------
            | APPROVAL TIMELINE
            |--------------------------------------------------------------------------
            */

            $timeline = [];

            $actualRequests = $supabase->get(
                'kpi_update_approvals',
                [
                    'kpi_id' => 'eq.' . $kpi['id'],
                    'select' => '*',
                    'order' => 'created_at.desc'
                ]
            ) ?? [];

            foreach ($actualRequests as $row) {

                $timeline[] = [

                    'type'
                        => 'Actual Update',

                    'status'
                        => $row['status'] ?? '-',

                    'by'
                        => $row['requested_by_name']
                        ?? '-',

                    'date'
                        => $row['created_at']
                        ?? null,

                    'approver'
                        => $row['approver_name']
                        ?? '-',
                ];
            }

            $editRequests = $supabase->get(
                'kpi_target_change_requests',
                [
                    'kpi_id' => 'eq.' . $kpi['id'],
                    'select' => '*',
                    'order' => 'created_at.desc'
                ]
            ) ?? [];

            foreach ($editRequests as $row) {

                /*
                |--------------------------------------------------------------------------
                | REQUEST EVENT
                |--------------------------------------------------------------------------
                */

                $timeline[] = [

                    'type'
                        => 'Target Change Requested',

                    'status'
                        => 'requested',

                    'date'
                        => $row['created_at']
                        ?? null,

                    'by'
                        => $row['requested_by_name']
                        ?? '-',

                    'base_from' =>

                        ($row['field_name'] ?? null)
                        === 'base_target'

                            ? ($row['old_value'] ?? 0)

                            : ($row['old_base_target'] ?? 0),

                    'base_to' =>

                        ($row['field_name'] ?? null)
                        === 'base_target'

                            ? ($row['requested_value'] ?? 0)

                            : ($row['new_base_target'] ?? 0),

                    'stretch_from' =>

                        ($row['field_name'] ?? null)
                        === 'stretch_target'

                            ? ($row['old_value'] ?? 0)

                            : ($row['old_stretch_target'] ?? 0),

                    'stretch_to' =>

                        ($row['field_name'] ?? null)
                        === 'stretch_target'

                            ? ($row['requested_value'] ?? 0)

                            : ($row['new_stretch_target'] ?? 0),

                    'reason'
                        => $row['reason']
                        ?? '-',

                ];

                /*
                |--------------------------------------------------------------------------
                | APPROVED EVENT
                |--------------------------------------------------------------------------
                */

                if(
                    ($row['status'] ?? '')
                    === 'approved'
                ){

                    $timeline[] = [

                        'type'
                            => 'Approved',

                        'status'
                            => 'approved',

                        'date'
                            => $row['approved_at']
                            ?? $row['updated_at']
                            ?? null,

                        'approver'
                            => $row['approved_by_name']
                            ?? $row['approver_name']
                            ?? '-',

                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | REJECTED EVENT
                |--------------------------------------------------------------------------
                */

                if(
                    ($row['status'] ?? '')
                    === 'rejected'
                ){

                    $timeline[] = [

                        'type'
                            => 'Rejected',

                        'status'
                            => 'rejected',

                        'date'
                            => $row['rejected_at']
                            ?? $row['updated_at']
                            ?? null,

                        'approver'
                            => $row['rejected_by_name']
                            ?? $row['approver_name']
                            ?? '-',

                        'reason'
                            => $row['rejection_reason']
                            ?? $row['reject_reason']
                            ?? '-',

                    ];
                }
            }

            $deleteRequests = $supabase->get(
                'kpi_delete_requests',
                [
                    'kpi_id' => 'eq.' . $kpi['id'],
                    'select' => '*',
                    'order' => 'created_at.desc'
                ]
            ) ?? [];

            foreach ($deleteRequests as $row) {

                $timeline[] = [

                    'type'
                        => 'Delete Request',

                    'status'
                        => $row['status'] ?? '-',

                    'by'
                        => $row['requested_by_name']
                        ?? '-',

                    'date'
                        => $row['created_at']
                        ?? null,

                    'approver'
                        => $row['approver_name']
                        ?? '-',
                ];
            }

            $kpi['approval_timeline']
                = collect($timeline)
                    ->sortByDesc('date')
                    ->values()
                    ->toArray();

            /*
            |--------------------------------------------------------------------------
            | PENDING DELETE
            |--------------------------------------------------------------------------
            */

            $pendingDelete = $supabase->get(
                'kpi_delete_requests',
                [
                    'kpi_id' => 'eq.' . $kpi['id'],
                    'status' => 'eq.pending',
                    'select' => 'id',
                    'limit' => 1,
                ]
            ) ?? [];

            $kpi['has_pending_delete_request']
                = !empty($pendingDelete);

            /*
            |--------------------------------------------------------------------------
            | PENDING EDIT
            |--------------------------------------------------------------------------
            */

            $pendingEdit = $supabase->get(
                'kpi_target_change_requests',
                [
                    'kpi_id' => 'eq.' . $kpi['id'],
                    'status' => 'eq.pending',
                    'select' => 'id',
                    'limit' => 1,
                ]
            ) ?? [];

            $kpi['has_pending_edit_request']
                = !empty($pendingEdit);

            return $kpi;
       })->toArray();

        return view('kpi.index', array_merge([

            'user' => $user,

            'permission' => $permission,

            'fy' => $fy,

            'employees' => $employees,

            'kpis' => $kpis,

        ], $this->sidebarData($supabase, $user)));
    }

    public function create(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);

        $fy = $request->get(
            'financial_year',
            $this->currentFY()
        );

        /*
        |--------------------------------------------------------------------------
        | REPORTING STAFF
        |--------------------------------------------------------------------------
        | Ambil semua staff yang reporting kepada user sekarang
        | untuk digunakan dalam Assign KPI dropdown.
        |--------------------------------------------------------------------------
        */

        $reportingStaff = $supabase->get('employees', [
            'reports_to_id' => 'eq.' . $user['id'],
            'company_code' => 'eq.' . $user['company_code'],
            'is_active'    => 'eq.true',

            'select' => implode(',', [
                'id',
                'employee_id',
                'short_name',
                'role'
            ]),

            'order' => 'short_name.asc',
        ]) ?? [];

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view('kpi.create', array_merge([

            'user' => $user,

            'fy' => $fy,

            'reportingStaff' => $reportingStaff,

        ], $this->sidebarData($supabase, $user)));
    }

    public function store(Request $request, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        $permission = $this->permissionForRoleFromDb($supabase, $user['role']);

        if (!$permission['can_create']) {
            abort(403, 'You cannot create KPI.');
        }

        $validated = $request->validate([
            'financial_year' => 'nullable|string',
            'category' => 'required|string|max:255',
            'sub_category' => 'required|string|max:255',
            'kpi_title' => 'required|string|max:255',
            'kpi_description' => 'nullable|string',
            'unit' => 'required|string|in:number,currency,percentage',
            'base_target' => 'required|numeric|min:0',
            'stretch_target' => 'required|numeric|min:0',
            'actual_value' => 'nullable|numeric',
            'status' => 'required|string|in:not_started,on_track,at_risk,in_trouble,completed',
            'remark' => 'nullable|string',
            'assigned_employee_id' => 'nullable|string',

            'quarters' => 'nullable|array',
            'quarters.*.quarter' => 'nullable|string|in:Q1,Q2,Q3,Q4',
            'quarters.*.quarter_target' => 'nullable|numeric|min:0',
            'quarters.*.start_date' => 'nullable|date',
            'quarters.*.end_date' => 'nullable|date',
            'quarters.*.remark' => 'nullable|string',
            'quarters.*.quarter_title' => 'nullable|string|max:255',
            'quarters.*.quarter_description' => 'nullable|string',
            'quarters.*.status' => 'nullable|string|in:not_started,on_track,at_risk,in_trouble,completed',
        ]);

        if ((float) $validated['stretch_target'] < (float) $validated['base_target']) {
            return back()
                ->withErrors(['stretch_target' => 'Stretch must be greater than or equal to Base.'])
                ->withInput();
        }

        $quarterDateError = $this->validateQuarterDates($validated['quarters'] ?? []);

        if ($quarterDateError) {
            return back()
                ->withErrors($quarterDateError)
                ->withInput();
        }

        $fy = $validated['financial_year'] ?? $this->currentFY();

        $actualValue =
            $validated['actual_value']
            ?? 0;

        $achievement = $this->calculateAchievement(
            $validated['base_target'],
            $validated['stretch_target'],
            $actualValue
        );

        $payload = [
            'employee_id' => $user['id'],
            'department_code' => $user['department_code'],
            'company_code' => $user['company_code'],
            'created_by' => $user['id'],

            'financial_year' => $fy,
            'category' => $validated['category'],
            'sub_category' => $validated['sub_category'],
            'kpi_title' => $validated['kpi_title'],
            'kpi_description' => $validated['kpi_description'] ?? null,
            'unit' => $validated['unit'],
            'base_target' => $validated['base_target'],
            'stretch_target' => $validated['stretch_target'],
            'actual_value' => $actualValue,
            'achievement_percentage' => $achievement,
            'status' => $this->normalizeStatus($validated['status']),
            'remark' => $validated['remark'] ?? null,

            'created_at' => $this->nowMy(),
            'updated_at' => $this->nowMy(),
        ];

        $createdKpi = $supabase->insert('kpis', $payload);

        $kpiId = $createdKpi[0]['id'] ?? null;

        if (!$kpiId) {
            $latestKpi = $supabase->get('kpis', [
                'employee_id' => 'eq.' . $user['id'],
                'department_code' => 'eq.' . $user['department_code'],
                'financial_year' => 'eq.' . $fy,
                'kpi_title' => 'eq.' . $validated['kpi_title'],
                'select' => '*',
                'order' => 'created_at.desc',
                'limit' => '1',
            ]);

            $kpiId = $latestKpi[0]['id'] ?? null;
        }

        if (!$kpiId) {
            return back()
                ->withErrors(['kpi' => 'KPI created, but system failed to get KPI ID for quarter creation.'])
                ->withInput();
        }

        $this->upsertQuarters($supabase, $kpiId, $validated['quarters'] ?? []);

        if (!empty($validated['assigned_employee_id'])) {

            $existingAssignment = $supabase->get('kpi_assignments', [

                'kpi_id' => 'eq.' . $kpiId,

                'assigned_employee_id' => 'eq.' . $validated['assigned_employee_id'],

                'select' => '*',

            ]) ?? [];

            if (empty($existingAssignment)) {

                $supabase->safeInsert('kpi_assignments', [

                    'kpi_id' => $kpiId,

                    'owner_employee_id' => $user['id'],

                    'assigned_employee_id' => $validated['assigned_employee_id'],

                    'created_at' => $this->nowMy(),

                ]);

            }

        }

        return redirect()
            ->route('kpi.index')
            ->with('success', 'KPI created successfully.');
    }

    public function update(Request $request, $id, SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        $permission = $this->permissionForRoleFromDb($supabase, $user['role']);

        if (!$permission['can_update']) {
            abort(403, 'You cannot update KPI.');
        }

        $oldKpi = $this->findKpiOrFail($supabase, $id);

        /*
        |--------------------------------------------------------------------------
        | PREVENT DIRECT TARGET EDIT
        |--------------------------------------------------------------------------
        */

        if(
            $request->filled('base_target')
            ||
            $request->filled('stretch_target')
        ){
            abort(403,
                'Target changes require approval request.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | STRICT WEIGHTAGE OWNERSHIP
        |--------------------------------------------------------------------------
        |
        | User can ONLY update own KPI weightage.
        | Prevent manager / VP / forged request manipulation.
        |
        */

        if (
            request()->has('weightage')
            && (($oldKpi['employee_id'] ?? '') !== ($user['id'] ?? ''))
        ) {
            abort(403, 'You can only manage your own KPI weightage.');
        }

        if (!$this->canEditKpi($user, $oldKpi)) {
            abort(403, 'You cannot update this KPI.');
        }

        $validated = $request->validate([
            'category' => 'required|string',
            'sub_category' => 'required|string',
            'kpi_title' => 'required|string|max:255',
            'kpi_description' => 'nullable|string',
            'unit' => 'required|string|in:number,currency,percentage',
            'actual_value' => 'required|numeric|min:0',
            'status' => 'required|string|in:not_started,on_track,at_risk,in_trouble,completed',
            'remark' => 'nullable|string',
            'weightage' => 'nullable|numeric|min:0|max:100',

            'quarters' => 'nullable|array',
            'quarters.*.quarter' => 'nullable|string|in:Q1,Q2,Q3,Q4',
            'quarters.*.start_date' => 'nullable|date',
            'quarters.*.end_date' => 'nullable|date',
            'quarters.*.remark' => 'nullable|string',
            'quarters.*.quarter_title' => 'nullable|string|max:255',
            'quarters.*.quarter_description' => 'nullable|string',
            'quarters.*.status' => 'nullable|string|in:not_started,on_track,at_risk,in_trouble,completed',
        ]);

        $quarterDateError = $this->validateQuarterDates($validated['quarters'] ?? []);

        if ($quarterDateError) {
            return back()
                ->withErrors($quarterDateError)
                ->withInput();
        }

        $achievement = $this->calculateAchievement(
            $oldKpi['base_target'] ?? 0,
            $oldKpi['stretch_target'] ?? 0,
            $validated['actual_value']
        );

        $updateData = [
            'category' => $validated['category'],
            'sub_category' => $validated['sub_category'],
            'kpi_title' => $validated['kpi_title'],
            'kpi_description' => $validated['kpi_description'] ?? null,
            'base_target' => $oldKpi['base_target'],
            'stretch_target' => $oldKpi['stretch_target'],
            'unit' => $validated['unit'],
            'actual_value' => $validated['actual_value'],
            'achievement_percentage' => $achievement,
            'status' => $this->normalizeStatus($validated['status']),
            'remark' => $validated['remark'] ?? null,
            'updated_at' => $this->nowMy(),
            'weightage' => $validated['weightage'] ?? ($oldKpi['weightage'] ?? 0),
        ];

        if(
            !$supabase->safePatch(
                'kpis',
                [
                    'id' => 'eq.' . $id,
                ],
                $updateData
            )
        ){
            return back()
                ->withErrors([
                    'kpi' => 'Failed to update KPI.'
                ])
                ->withInput();
        }

        $this->upsertQuarters($supabase, $id, $validated['quarters'] ?? []);

        /*
        |--------------------------------------------------------------------------
        | KPI ASSIGNMENT
        |--------------------------------------------------------------------------
        */

        $this->recordKpiHistory($supabase, $oldKpi, $updateData, $id, $user);

        return redirect()
            ->route('kpi.index')
            ->with('success', 'KPI updated successfully.');
    }

    public function destroy(string $id, SupabaseService $supabase)
    {
        abort(403,
            'Direct KPI deletion is disabled. Approval required.'
        );
    }

    public function switchDepartment(Request $request)
    {
        $request->validate([
            'department_code' => 'required|string',
        ]);

        session([
            'selected_department_code' => $request->department_code,
        ]);

        return back();
    }

    private function canEditKpi(array $user, array $kpi): bool
    {
        $role = strtoupper(trim($user['role'] ?? ''));

        /*
        |--------------------------------------------------------------------------
        | ADMIN / SLT
        |--------------------------------------------------------------------------
        */

        if (in_array($role, ['ADMIN', 'SLT'])) {

            return ($user['company_code'] ?? null)
                === ($kpi['company_code'] ?? null);
        }

        /*
        |--------------------------------------------------------------------------
        | CCO / CCMO / VP
        |--------------------------------------------------------------------------
        */

        if (in_array($role, ['CCO', 'CCMO', 'VP'])) {

            return ($user['company_code'] ?? null)
                === ($kpi['company_code'] ?? null);
        }

        /*
        |--------------------------------------------------------------------------
        | MANAGER
        |--------------------------------------------------------------------------
        */

        if ($role === 'MANAGER') {

            return ($user['department_code'] ?? null)
                === ($kpi['department_code'] ?? null);
        }

        /*
        |--------------------------------------------------------------------------
        | EXECUTIVE
        |--------------------------------------------------------------------------
        */

        if ($role === 'EXECUTIVE') {

            return (
                ($user['id'] ?? null)
                ===
                ($kpi['employee_id'] ?? null)
            );
        }

        return false;
    }

    private function upsertQuarters(SupabaseService $supabase, string $kpiId, array $quarters): void
    {
        $year = (int) now('Asia/Kuala_Lumpur')->year;

        $defaultDates = [
            'Q1' => ['start' => $year . '-01-01', 'end' => $year . '-03-31'],
            'Q2' => ['start' => $year . '-04-01', 'end' => $year . '-06-30'],
            'Q3' => ['start' => $year . '-07-01', 'end' => $year . '-09-30'],
            'Q4' => ['start' => $year . '-10-01', 'end' => $year . '-12-31'],
        ];

        foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarterLabel) {
            $quarter = $quarters[$quarterLabel] ?? [];

            unset(
                $quarter['quarter_actual']
            );

            $existingQuarter = $supabase->get('kpi_quarters', [
                'kpi_id' => 'eq.' . $kpiId,
                'quarter' => 'eq.' . $quarterLabel,
                'select' => '*',
                'limit' => 1,
            ]);

            $currentQuarter = $existingQuarter[0] ?? null;

            $payload = [

                'kpi_id' => $kpiId,

                'quarter' => $quarterLabel,

                'quarter_title'
                    => $quarter['quarter_title']
                    ?? ($quarterLabel . ' Plan'),

                'quarter_description'
                    => $quarter['quarter_description']
                    ?? null,

                'quarter_target'
                    => isset($quarter['quarter_target'])
                    && $quarter['quarter_target'] !== ''
                    ? (float)$quarter['quarter_target']
                    : ($currentQuarter['quarter_target'] ?? 0),

                'start_date' => !empty($quarter['start_date'])
                    ? $quarter['start_date']
                    : $defaultDates[$quarterLabel]['start'],

                'end_date' => !empty($quarter['end_date'])
                    ? $quarter['end_date']
                    : $defaultDates[$quarterLabel]['end'],

                'status'
                    => $this->normalizeStatus(
                        $quarter['status']
                        ??
                        $currentQuarter['status']
                        ??
                        'not_started'
                    ),

                'remark'
                    => $quarter['remark']
                    ?? null,

                'updated_at'
                    => $this->nowMy(),

            ];

            $payload['quarter_actual']
                = $currentQuarter['quarter_actual']
                ?? 0;

            if (!empty($existingQuarter)) {
                $supabase->safePatch('kpi_quarters', [
                    'id' => 'eq.' . $existingQuarter[0]['id'],
                ], $payload);
            } else {
                $payload['created_at'] = $this->nowMy();
                $supabase->safeInsert(
                    'kpi_quarters',
                    $payload
                );
            }
        }
    }

    private function validateQuarterDates(array $quarters): ?array
    {
        $year = now('Asia/Kuala_Lumpur')->year;

        $ranges = [

            'Q1' => [
                'start' => "$year-01-01",
                'end'   => "$year-03-31",
            ],

            'Q2' => [
                'start' => "$year-04-01",
                'end'   => "$year-06-30",
            ],

            'Q3' => [
                'start' => "$year-07-01",
                'end'   => "$year-09-30",
            ],

            'Q4' => [
                'start' => "$year-10-01",
                'end'   => "$year-12-31",
            ],

        ];

        foreach (['Q1','Q2','Q3','Q4'] as $quarterLabel) {

            $quarter =
                $quarters[$quarterLabel]
                ?? [];

            $startDate =
                $quarter['start_date']
                ?? null;

            $endDate =
                $quarter['end_date']
                ?? null;

            if(
                empty($startDate)
                ||
                empty($endDate)
            ){
                continue;
            }

            if(
                $endDate < $startDate
            ){
                return [

                    "quarters.$quarterLabel.end_date" =>

                    "$quarterLabel end date must be after start date."

                ];
            }

            $allowedStart =
                $ranges[$quarterLabel]['start'];

            $allowedEnd =
                $ranges[$quarterLabel]['end'];

            if(

                $startDate < $allowedStart
                ||

                $startDate > $allowedEnd
                ||

                $endDate < $allowedStart
                ||

                $endDate > $allowedEnd

            ){

                return [

                    "quarters.$quarterLabel.start_date" =>

                    "$quarterLabel timeline must stay within "
                    .
                    $allowedStart
                    .
                    " until "
                    .
                    $allowedEnd

                ];
            }
        }

        return null;
    }

    private function findKpiOrFail(SupabaseService $supabase, string $id): array
    {
        $kpi = $supabase->get('kpis', [
            'id' => 'eq.' . $id,
            'select' => '*',
        ])[0] ?? null;

        if (!$kpi) {
            abort(404, 'KPI not found.');
        }

        return $kpi;
    }

    private function calculateAchievement($baseTarget, $stretchTarget, $actualValue): float
    {
        $base = max(0, (float) ($baseTarget ?? 0));
        $stretch = max($base, (float) ($stretchTarget ?? 0));
        $actual = max(0, (float) ($actualValue ?? 0));

        if ($base <= 0) {
            return 0;
        }

        if ($actual <= $base) {
            return round(($actual / $base) * 100, 2);
        }

        if ($stretch > $base) {
            return round(min(200, 100 + (($actual - $base) / ($stretch - $base)) * 100), 2);
        }

        return 100;
    }

    public function weightage(SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);
        $permission = $this->permissionForRoleFromDb(
                $supabase,
                $user['role']
        );

        $fy = $this->currentFY();

        $employees = [
                [
                    'id' => $user['id'],
                    'short_name' => $user['short_name']
                ]
        ];

        $kpis = $supabase->get('kpis', [
            'employee_id' => 'eq.' . $user['id'],
            'financial_year' => 'eq.' . $this->currentFY(),
            'select' => '*',
            'order' => 'created_at.desc',
        ]) ?? [];

        /*
            Load quarter data for score calculation
        */

        $kpis = collect($kpis)->map(function ($kpi) use ($supabase) {

            $quarters = $supabase->get('kpi_quarters', [
                'kpi_id' => 'eq.' . $kpi['id'],
                'select' => '*',
                'order' => 'quarter.asc',
            ]) ?? [];

            $kpi['quarters'] = $quarters;

            return $kpi;

        })->toArray();

        $kpiCountByUser = collect($kpis)
            ->groupBy('employee_id')
            ->map(fn($items) => count($items))
            ->toArray();

        $kpiCountByDepartment = collect($kpis)
            ->groupBy('department_code')
            ->map(fn($items) => count($items))
            ->toArray();

        return view('kpi.weightage', array_merge([

            'user' => $user,
            'permission' => $permission,
            'fy' => $fy,
            'employees' => $employees,
            'kpis' => $kpis,
            'kpiCountByUser' => $kpiCountByUser,
            'kpiCountByDepartment' => $kpiCountByDepartment,

        ], $this->sidebarData($supabase, $user)));
    }

    private function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'not_started' => 'not_started',
            'on_track', 'monitoring' => 'on_track',
            'at_risk', 'risk' => 'at_risk',
            'in_trouble', 'critical', 'off_track', 'overdue' => 'in_trouble',
            'completed' => 'completed',
            default => 'not_started',
        };
    }


    private function recordKpiHistory(
        SupabaseService $supabase,
        array $oldKpi,
        array $newData,
        string $kpiId,
        array $user
    ): void {
        $fieldsToTrack = [
            'category',
            'sub_category',
            'kpi_title',
            'kpi_description',
            'base_target',
            'stretch_target',
            'actual_value',
            'unit',
            'status',
            'remark',
            'weightage',
        ];

        foreach ($fieldsToTrack as $field) {
            $oldValue = (string) ($oldKpi[$field] ?? '');
            $newValue = (string) ($newData[$field] ?? '');

            if ($oldValue !== $newValue) {
                $supabase->safeInsert('kpi_histories', [
                    'kpi_id' => $kpiId,
                    'edited_by' => $user['id'] ?? null,
                    'edited_by_name' => $user['short_name'] ?? 'Unknown',
                    'field_name' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'created_at' => $this->nowMy(),
                ]);
            }
        }
    }

    public function myDepartmentKpi(SupabaseService $supabase)
    {
        $user = $this->currentUser($supabase);

        $fy = $this->currentFY();

        /*
        |--------------------------------------------------------------------------
        | GET DEPARTMENT KPI
        |--------------------------------------------------------------------------
        */

        $kpis = $supabase->get('kpis', [

            'company_code' => 'eq.' . $user['company_code'],

            'department_code' => 'eq.' . $user['department_code'],

            'financial_year' => 'eq.' . $fy,

            'select' => '*',

            'order' => 'created_at.desc',

        ]) ?? [];

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEES
        |--------------------------------------------------------------------------
        */

        $employees = $supabase->get('employees', [

            'company_code' => 'eq.' . $user['company_code'],

            'department_code' => 'eq.' . $user['department_code'],

            'is_active' => 'eq.true',

            'select' => implode(',', [

                'id',
                'employee_id',
                'short_name',
                'role',
                'department_code'

            ]),

        ]) ?? [];

        $employeeMap = collect($employees)->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | LOAD QUARTERS
        |--------------------------------------------------------------------------
        */

        $kpis = collect($kpis)->map(function ($kpi) use ($supabase, $employeeMap) {

            $employee = $employeeMap->get($kpi['employee_id']);

            $kpi['employee_name']
                = $employee['short_name'] ?? 'Unknown';

            $kpi['employee_role']
                = $employee['role'] ?? '-';

            $quarters = $supabase->get('kpi_quarters', [

                'kpi_id' => 'eq.' . $kpi['id'],

                'select' => '*',

                'order' => 'quarter.asc',

            ]) ?? [];

            $kpi['quarters'] = $quarters;

            return $kpi;

        })->toArray();

        /*
        |--------------------------------------------------------------------------
        | DEPARTMENT PERFORMANCE
        |--------------------------------------------------------------------------
        */

        $departmentPerformance = collect($kpis)->avg(function ($kpi) {

            $quarters = collect($kpi['quarters'] ?? []);

            $target = $quarters->sum(function ($q) {

                return (float) ($q['quarter_target'] ?? 0);

            });

            $actual = $quarters->sum(function ($q) {

                return (float) ($q['quarter_actual'] ?? 0);

            });

            if ($target <= 0) {
                return 0;
            }

            return ($actual / $target) * 100;

        });

        return view('kpi.my-department-kpi', array_merge([

            'user' => $user,

            'fy' => $fy,

            'kpis' => $kpis,

            'employees' => $employees,

            'departmentPerformance'
                => round($departmentPerformance, 2),

        ], $this->sidebarData($supabase, $user)));
    }

    public function requestTargetChange(
        Request $request,
        $id,
        SupabaseService $supabase
        )
        {
        $user = $this->currentUser(
        $supabase
        );

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'new_base_target'
                => 'required|numeric|min:0',

            'new_stretch_target'
                => 'required|numeric|min:0',

            'reason'
                => 'required|string|min:30',

        ]);

        /*
        |--------------------------------------------------------------------------
        | STRETCH >= BASE
        |--------------------------------------------------------------------------
        */

        if(
            (float)$validated['new_stretch_target']
            <
            (float)$validated['new_base_target']
        ){
            return response()->json([

                'success' => false,

                'message'
                    => 'Stretch target must be greater than or equal to Base target.'

            ],422);
        }

        /*
        |--------------------------------------------------------------------------
        | KPI
        |--------------------------------------------------------------------------
        */

        $kpi =
            $this->findKpiOrFail(
                $supabase,
                $id
            );

        /*
        |--------------------------------------------------------------------------
        | DUPLICATE CHECK
        |--------------------------------------------------------------------------
        */

        $existing =
            $supabase->first(

                'kpi_target_change_requests',

                [

                    'kpi_id'
                        => 'eq.' . $id,

                    'status'
                        => 'eq.pending',

                    'select'
                        => '*'

                ]

            );

        if($existing){

            return response()->json([

                'success' => false,

                'message'
                    => 'Pending request already exists.'

            ],409);
        }

        /*
        |--------------------------------------------------------------------------
        | APPROVER
        |--------------------------------------------------------------------------
        */

        $approver =
            $this->hierarchyService
                ->getApprover(
                    $user
                );

        if(!$approver){

            return response()->json([

                'success' => false,

                'message'
                    => 'Approver not found.'

            ],422);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE REQUEST
        |--------------------------------------------------------------------------
        */

        $inserted =
            $supabase->safeInsert(

                'kpi_target_change_requests',

                [

                    'kpi_id'
                        => $id,

                    'requested_by'
                        => $user['id'],

                    'requested_by_name'
                        => $user['short_name'],

                    'requested_role'
                        => $user['role'],

                    'approver_id'
                        => $approver['id'],

                    'approver_name'
                        => $approver['short_name'],

                    'approver_role'
                        => $approver['role'],

                    /*
                    |--------------------------------------------------------------------------
                    | LEGACY REQUIRED FIELD
                    |--------------------------------------------------------------------------
                    */

                    'field_name'
                        => 'target_change',

                    'old_value'
                        => $kpi['base_target'],

                    'requested_value'
                        => $validated['new_base_target'],

                    /*
                    |--------------------------------------------------------------------------
                    | NEW FIELD
                    |--------------------------------------------------------------------------
                    */

                    'old_base_target'
                        => $kpi['base_target'],

                    'new_base_target'
                        => $validated['new_base_target'],

                    'old_stretch_target'
                        => $kpi['stretch_target'],

                    'new_stretch_target'
                        => $validated['new_stretch_target'],

                    'reason'
                        => $validated['reason'],

                    'status'
                        => 'pending',

                    'created_at'
                        => $this->nowMy(),

                ]

            );

        if(!$inserted){

            return response()->json([

                'success' => false,

                'message'
                    => 'Failed to create request.'

            ],500);
        }

        return response()->json([

            'success' => true,

            'message'
                => 'Target change request submitted.'

        ]);

        }


    /*
    |--------------------------------------------------------------------------
    | REQUEST DELETE
    |--------------------------------------------------------------------------
    */

    public function requestDelete($id)
    {
        $kpi = $this->findKpiOrFail($this->supabase, $id);

        return view('kpi.request-delete', [
            'kpi' => $kpi,
            'user' => $this->currentUser($this->supabase),
        ]);
    }

    public function submitDeleteRequest(
        Request $request,
        $id
    )
    {
        $user = $this->currentUser(
            $this->supabase
        );

        $kpi = $this->findKpiOrFail(
            $this->supabase,
            $id
        );

        /*
        |--------------------------------------------------------------------------
        | PERMISSION
        |--------------------------------------------------------------------------
        */

        if(
            !$this->canEditKpi(
                $user,
                $kpi
            )
        ){
            abort(
                403,
                'You cannot delete this KPI.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'reason' => [
                'required',
                'string',
                'min:30',
                'max:2000',
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | BLOCK IF KPI HAS PENDING APPROVALS
        |--------------------------------------------------------------------------
        */

        $pendingQuarterApproval =
            $this->supabase->get(
                'kpi_update_approvals',
                [
                    'kpi_id'
                        => 'eq.' . $id,

                    'status'
                        => 'eq.pending',

                    'select'
                        => 'id',

                    'limit'
                        => 1,
                ]
            ) ?? [];

        $pendingTargetApproval =
            $this->supabase->get(
                'kpi_target_change_requests',
                [
                    'kpi_id'
                        => 'eq.' . $id,

                    'status'
                        => 'eq.pending',

                    'select'
                        => 'id',

                    'limit'
                        => 1,
                ]
            ) ?? [];

        if(
            !empty($pendingQuarterApproval)
            ||
            !empty($pendingTargetApproval)
        ){

            return response()->json([

                'success'
                    => false,

                'message'
                    => 'Resolve pending approvals first.'

            ],409);
        }

        /*
        |--------------------------------------------------------------------------
        | BLOCK DUPLICATE DELETE REQUEST
        |--------------------------------------------------------------------------
        */

        $existingRequest =
            $this->supabase->get(
                'kpi_delete_requests',
                [
                    'kpi_id'
                        => 'eq.' . $id,

                    'status'
                        => 'eq.pending',

                    'select'
                        => '*',
                ]
            ) ?? [];

        if(
            !empty($existingRequest)
        ){

            return response()->json([

                'success'
                    => false,

                'message'
                    => 'Pending delete request already exists.'

            ],409);
        }

        /*
        |--------------------------------------------------------------------------
        | APPROVER
        |--------------------------------------------------------------------------
        */

        $approver =
            $this->hierarchyService
                ->getApprover(
                    $user
                );

        if(
            !$approver
        ){

            return response()->json([

                'success'
                    => false,

                'message'
                    => 'Approver not found.'

            ],422);
        }

        $approverId =
            $approver['id']
            ?? null;

        if(
            !$approverId
        ){

            return response()->json([

                'success'
                    => false,

                'message'
                    => 'Approver ID missing.'

            ],422);
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT REQUEST
        |--------------------------------------------------------------------------
        */

        $payload = [

            'kpi_id'
                => $id,

            'requested_by'
                => $user['id'],

            'requested_by_name'
                => $user['short_name']
                    ?? $user['full_name']
                    ?? $user['name']
                    ?? 'Unknown',

            'requested_role'
                => $user['role']
                    ?? null,

            'approver_id'
                => $approverId,

            'approver_name'
                => $approver['short_name']
                    ?? $approver['full_name']
                    ?? $approver['name']
                    ?? null,

            'approver_role'
                => $approver['role']
                    ?? null,

            'reason'
                => trim(
                    $validated['reason']
                ),

            'status'
                => 'pending',

            'created_at'
                => $this->nowMy(),

        ];

        try {

            $result =
                $this->supabase->insert(
                    'kpi_delete_requests',
                    $payload
                );

        } catch (\Throwable $e) {

            Log::error(
                'DELETE REQUEST INSERT FAILED',
                [
                    'payload' =>
                        $payload,

                    'error' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([

                'success'
                    => false,

                'message'
                    => $e->getMessage(),

            ],500);
        }

        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success'
                => true,

            'message'
                => 'Delete request submitted successfully.',

            'data'
                => $result,

        ]);
    }

        /*
        |--------------------------------------------------------------------------
        | UPDATE QUARTER ACTUAL
        |--------------------------------------------------------------------------
        */

        public function updateQuarterActual(Request $request)
        {
            $user = $this->currentUser($this->supabase);

            $validated = $request->validate([
                'kpi_id' => 'required|string',
                'quarter' => 'required|string|in:Q1,Q2,Q3,Q4',
                'remark' => 'nullable|string',
            ]);

            /*
            |--------------------------------------------------------------------------
            | KPI
            |--------------------------------------------------------------------------
            */

            $kpi = $this->findKpiOrFail(
                $this->supabase,
                $validated['kpi_id']
            );

            /*
            |--------------------------------------------------------------------------
            | LOCK EXPIRED QUARTER FOR EVERYONE
            |--------------------------------------------------------------------------
            |
            | Everyone requires approval
            | after quarter expired.
            |
            */

            /*
            |--------------------------------------------------------------------------
            | FIND QUARTER
            |--------------------------------------------------------------------------
            */

            $quarters = $this->supabase->get('kpi_quarters', [
                'kpi_id' => 'eq.' . $validated['kpi_id'],
                'quarter' => 'eq.' . $validated['quarter'],
                'select' => '*',
                'limit' => 1,

            ]) ?? [];

            if (empty($quarters)) {

                return response()->json([

                    'success' => false,

                    'message' => 'Quarter not found.'

                ], 404);
            }

            $quarter = $quarters[0];

            /*
            |--------------------------------------------------------------------------
            | UPDATE QUARTER
            |--------------------------------------------------------------------------
            */

            if(
                !$this->supabase->safePatch(
                    'kpi_quarters',
                    [
                        'id' => 'eq.' . $quarter['id'],
                    ],
                    [
                        'updated_at' => $this->nowMy(),
                    ]
                )
            ){
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update quarter.'
                ],500);
            }

            /*
            |--------------------------------------------------------------------------
            | RECALCULATE OVERALL ACTUAL
            |--------------------------------------------------------------------------
            */

            $allQuarters = $this->supabase->get('kpi_quarters', [
                'kpi_id' => 'eq.' . $validated['kpi_id'],
                'select' => '*',
            ]) ?? [];

            return response()->json([
                'success' => true,
                'message'
                    => 'Quarter details updated.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | REQUEST QUARTER APPROVAL
        |--------------------------------------------------------------------------
        */

        public function requestQuarterApproval(Request $request)
        {
            $user = $this->currentUser($this->supabase);

            $validated = $request->validate([

                'kpi_id' => 'required|string',

                'quarter' => 'required|string|in:Q1,Q2,Q3,Q4',

                'actual' => 'nullable|numeric|min:0',

                'remark' => 'nullable|string',

            ]);

            /*
            |--------------------------------------------------------------------------
            | PREVENT DUPLICATE PENDING REQUEST
            |--------------------------------------------------------------------------
            */

            $existingRequest = $this->supabase->get(
                'kpi_update_approvals',
                [
                    'kpi_id' => 'eq.' . $validated['kpi_id'],
                    'quarter' => 'eq.' . $validated['quarter'],
                    'status' => 'eq.pending',
                    'select' => '*',
                ]

            ) ?? [];

            if (!empty($existingRequest)) {

                return response()->json([

                    'success' => false,

                    'message' => 'Pending approval already exists.'

                ], 409);
            }

            $approverId =
                $this->hierarchyService
                    ->getApproverId($user);

            if(!$approverId){
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Approver not found.'
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE REQUEST
            |--------------------------------------------------------------------------
            */

            if(
                !$this->supabase->safeInsert(
                    'kpi_update_approvals',
                    [
                        'kpi_id' => $validated['kpi_id'],
                        'quarter' => $validated['quarter'],
                        'requested_actual' => $validated['actual'] ?? 0,
                        'request_remark' => $validated['remark'] ?? null,
                        'requested_by' => $user['id'] ?? null,
                        'requested_by_name' => $user['short_name'] ?? 'Unknown',
                        'approver_id' => $approverId,
                        'status' => 'pending',
                        'created_at' => $this->nowMy(),
                    ]
                )
            ){
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create approval request.'
                ],500);
            }

        return response()->json([
                'success' => true,
                'message' => 'Approval request submitted successfully.'
        ]);


    }

    /*
    |--------------------------------------------------------------------------
    | SUBMIT ACTUAL UPDATE REQUEST
    |--------------------------------------------------------------------------
    |
    | Used when user wants to change actual value
    | after quarter is locked.
    |
    */

    public function submitActualUpdateRequest(
        Request $request,
        string $kpiId,
        string $quarterId
    )
    {
        $user = $this->currentUser(
            $this->supabase
        );

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'requested_actual'
                => 'required|numeric|min:0',

            'reason'
                => 'required|min:20|max:1000',

        ]);

        /*
        |--------------------------------------------------------------------------
        | KPI
        |--------------------------------------------------------------------------
        */

        $this->findKpiOrFail(
            $this->supabase,
            $kpiId
        );

        /*
        |--------------------------------------------------------------------------
        | QUARTER
        |--------------------------------------------------------------------------
        */

        $quarter = $this->supabase->first(
            'kpi_quarters',
            [
                'id'
                    => 'eq.' . $quarterId,

                'kpi_id'
                    => 'eq.' . $kpiId,

                'select'
                    => '*',
            ]
        );

        if (!$quarter) {
            return response()->json([
                'success' => false,
                'message' => 'Quarter not found.'
            ],404);
        }

        $currentActual =

        (float)
        (
            $quarter['quarter_actual']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | PHASE 7
        | CALCULATE RISK
        |--------------------------------------------------------------------------
        */

        $variance = 0;

        if ($currentActual <= 0) {

            if (
                (float)$validated['requested_actual']
                > 0
            ) {

                $variance = 999;
            }

        } else {

            $variance = (

                (
                    (
                        (float)$validated['requested_actual']
                        -
                        (float)$currentActual
                    )

                    /

                    (float)$currentActual

                )

                * 100
            );
        }

        $riskLevel = 'LOW';

        if (
            abs($variance) >= 100
        ) {

            $riskLevel = 'CRITICAL';

        }
        elseif (
            abs($variance) >= 50
        ) {

            $riskLevel = 'HIGH';

        }
        elseif (
            abs($variance) >= 20
        ) {

            $riskLevel = 'MEDIUM';

        }

        /*
        |--------------------------------------------------------------------------
        | QUARTER LOCK CHECK
        |--------------------------------------------------------------------------
        */

        $today = now('Asia/Kuala_Lumpur')
            ->startOfDay();

        $endDate = Carbon::parse(
            $quarter['end_date']
        )->startOfDay();

        if (!$today->gt($endDate)) {

            return response()->json([

                'success' => false,

                'message'
                    => 'Quarter is still open. Use normal update.'

            ],422);
        }

        /*
        |--------------------------------------------------------------------------
        | DUPLICATE CHECK
        |--------------------------------------------------------------------------
        */

        $existing = $this->supabase->get(
            'kpi_update_approvals',
            [
                'quarter_id' => 'eq.' . $quarterId,
                'status' => 'eq.pending',
                'select' => 'id',
                'limit' => 1,
            ]
        ) ?? [];

        if (!empty($existing)) {
            return response()->json([
                'success' => false,
                'message'
                    => 'Pending approval already exists.'
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | APPROVER
        |--------------------------------------------------------------------------
        */

        $approver =
            $this->hierarchyService
                ->getApprover($user);

        /*
        |--------------------------------------------------------------------------
        | SLT / ADMIN AUTO APPROVE
        |--------------------------------------------------------------------------
        */

        if($approver === null){

            return $this->autoApproveActual(
                $kpiId,
                $quarterId,
                $quarter,
                $currentActual,
                $validated,
                $user,
                $variance,
                $riskLevel
            );
        }

        $approverId =
            $approver['id'] ?? null;

        if (!$approverId) {

            return response()->json([
                'success' => false,
                'message' => 'Approver ID not found.'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE REQUEST
        |--------------------------------------------------------------------------
        */

        $inserted = $this->supabase->safeInsert(
            'kpi_update_approvals',
            [

                'kpi_id'
                    => $kpiId,

                'quarter_id'
                    => $quarterId,

                'quarter'
                    => $quarter['quarter'] ?? null,

                'old_actual'
                    => $currentActual,

                'requested_actual'
                    => $validated['requested_actual'],

                'reason'
                    => $validated['reason'],

                'variance'
                    => round($variance, 2),

                'risk_level'
                    => $riskLevel,

                'requested_by'
                    => $user['id'],

                'requested_by_name'
                    => $user['short_name'] ?? 'Unknown',

                'requested_by_role'
                    => $user['role']
                    ?? null,

                'approver_id'
                    => $approverId,

                'approver_name'
                    => $approver['short_name']
                    ?? null,

                'approver_role'
                    => $approver['role']
                    ?? null,

                'status'
                    => 'pending',

                'created_at'
                    => $this->nowMy(),

            ]
        );

        if (!$inserted) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request.'
            ], 500);
        }

        return response()->json([

            'success' => true,

            'message'
                => 'Actual update request submitted successfully.'

        ]);
    }

    private function autoApproveActual(
        string $kpiId,
        string $quarterId,
        array $quarter,
        float $currentActual,
        array $validated,
        array $user,
        float $variance,
        string $riskLevel
    )
    {
        $approval = [

            'id' => null,

            'kpi_id'
                => $kpiId,

            'quarter_id'
                => $quarterId,

            'quarter'
                => $quarter['quarter'] ?? null,

            'old_actual'
                => $currentActual,

            'requested_actual'
                => $validated['requested_actual'],

            'reason'
                => $validated['reason'],

            'status'
                => 'approved',

            'variance'
                => round($variance, 2),

            'risk_level'
                => $riskLevel,

        ];

        $insertResult =
            $this->supabase->insert(
                'kpi_update_approvals',
                [

                    'kpi_id'
                        => $kpiId,

                    'quarter_id'
                        => $quarterId,

                    'quarter'
                        => $quarter['quarter'],

                    'old_actual'
                        => $currentActual,

                    'requested_actual'
                        => $validated['requested_actual'],

                    'reason'
                        => $validated['reason'],

                    'variance'
                        => round($variance, 2),

                    'risk_level'
                        => $riskLevel,

                    'requested_by'
                        => $user['id'],

                    'requested_by_name'
                        => $user['short_name'],

                    'requested_by_role'
                        => $user['role'],

                    'approver_role'
                        => $user['role'],

                    'approver_id'
                        => $user['id'],

                    'approver_name'
                        => $user['short_name'],

                    'status'
                        => 'approved',

                    'approved_at'
                        => $this->nowMy(),

                    'created_at'
                        => $this->nowMy(),

                ]
            );

        if(
            empty($insertResult)
            ||
            empty($insertResult[0]['id'])
        ){
            return response()->json([
                'success' => false,
                'message' => 'Failed creating approval record.'
            ],500);
        }

        $approval['id']
            = $insertResult[0]['id'];

        app(
            \App\Services\ApprovalActionService::class
        )->autoApproveQuarter(
            $approval,
            $user['id'],
            $user['short_name']
        );

        return response()->json([
            'success' => true,
            'message'
                => 'Actual updated successfully.'
        ]);
    }

    public function saveQuarterStatus(
        Request $request,
        string $quarterId
    )
    {
        $validated = $request->validate([

            'status'
                => 'required|in:not_started,on_track,at_risk,in_trouble,completed',

        ]);

        $user = $this->currentUser(
            $this->supabase
        );

        $quarter = $this->supabase->first(
            'kpi_quarters',
            [
                'id' => 'eq.' . $quarterId,
                'select' => '*',
            ]
        );

        if(!$quarter){
            abort(404);
        }

        $kpi = $this->findKpiOrFail(
            $this->supabase,
            $quarter['kpi_id']
        );

        if(
            !$this->canEditKpi(
                $user,
                $kpi
            )
        ){
            abort(403);
        }

        $updated = $this->supabase->safePatch(

            'kpi_quarters',

            [
                'id'
                    => 'eq.' . $quarterId,
            ],

            [
                'status'
                    => $validated['status'],

                'updated_at'
                    => $this->nowMy(),
            ]

        );

        if(!$updated){

            return response()->json([

                'success' => false,

                'message'
                    => 'Failed updating status.'

            ],500);
        }

        return response()->json([

            'success' => true,

            'message'
                => 'Status updated successfully.'

        ]);
    }

    public function bulkUpdateWeightage(Request $request)
    {
        $user = $this->currentUser($this->supabase);

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'weightages' => 'required|array',

            'weightages.*'
                => 'nullable|numeric|min:0|max:100',

        ]);

        $weightages = $validated['weightages'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | TOTAL VALIDATION
        |--------------------------------------------------------------------------
        */

        $total = collect($weightages)

            ->sum(fn($v) => (float)$v);

        $total = round($total, 2);

        if(abs($total - 100) > 0.01)
        {
            return response()->json([
                'success' => false,
                'message' => 'Total weightage must equal 100%'
            ],422);
        }

        /*
        |--------------------------------------------------------------------------
        | GET USER OWN KPI
        |--------------------------------------------------------------------------
        */

        $myKpis = $this->supabase->get('kpis', [

            'employee_id'
                => 'eq.' . ($user['id'] ?? ''),

            'financial_year'
                => 'eq.' . $this->currentFY(),

            'select'
                => 'id,employee_id,weightage',

        ]) ?? [];

        /*
        |--------------------------------------------------------------------------
        | OWN KPI IDS
        |--------------------------------------------------------------------------
        */

        $allowedKpiIds = collect($myKpis)
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();
        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        foreach($weightages as $kpiId => $weightage){

            /*
            |--------------------------------------------------------------------------
            | SECURITY CHECK
            |--------------------------------------------------------------------------
            */

            if(!in_array($kpiId, $allowedKpiIds)){

                continue;
            }

            if(empty($allowedKpiIds)){

                return response()->json([

                    'success'=>false,

                    'message'=>'No KPI found.'

                ],422);
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE KPI
            |--------------------------------------------------------------------------
            */

            $this->supabase->safePatch(

                'kpis',

                [

                    'id'
                        => 'eq.' . $kpiId,

                ],

                [

                    'weightage'
                        => round((float)$weightage, 2),

                    'updated_at'
                        => $this->nowMy(),

                ]

            );
        }

        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'Weightage updated successfully.'
        ]);
    }
}
