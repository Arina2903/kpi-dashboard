<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class LinkageController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    private function currentUser(): array
    {
        $employeeUuid = session('employee_uuid');
        if (!$employeeUuid) abort(403, 'Not logged in.');
        $rows = $this->supabase->get('employees', [
            'id'        => 'eq.' . $employeeUuid,
            'is_active' => 'eq.true',
            'select'    => '*',
        ]);
        if (empty($rows)) abort(403, 'Employee not found.');
        return $rows[0];
    }

    private function nowMy(): string
    {
        return now()->timezone('Asia/Kuala_Lumpur')->toDateTimeString();
    }

    private function currentFY(): string
    {
        return 'FY' . now()->year;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assignee_id'     => 'required|string',
            'category'        => 'required|string',
            'sub_category'    => 'required|string',
            'assigned_target' => 'required|numeric|min:0',
            'unit'            => 'required|in:number,currency,percentage',
        ]);

        $user = $this->currentUser();
        $fy   = $this->currentFY();

        // Fetch assignee name
        $assignee = $this->supabase->first('employees', [
            'id'     => 'eq.' . $validated['assignee_id'],
            'select' => 'id,short_name,role',
        ]);
        if (!$assignee) return back()->with('error', 'Assignee not found.');

        $now = $this->nowMy();

        // Check if linkage already exists
        $existing = $this->supabase->first('kpi_linkages', [
            'company_code'   => 'eq.' . $user['company_code'],
            'financial_year' => 'eq.' . $fy,
            'assigner_id'    => 'eq.' . $user['id'],
            'assignee_id'    => 'eq.' . $validated['assignee_id'],
            'sub_category'   => 'eq.' . $validated['sub_category'],
        ]);

        if ($existing) {
            $this->supabase->safePatch('kpi_linkages', ['id' => 'eq.' . $existing['id']], [
                'assigned_target' => $validated['assigned_target'],
                'unit'            => $validated['unit'],
                'category'        => $validated['category'],
                'updated_at'      => $now,
            ]);
        } else {
            $this->supabase->safeInsert('kpi_linkages', [
                'company_code'    => $user['company_code'],
                'financial_year'  => $fy,
                'assigner_id'     => $user['id'],
                'assigner_name'   => $user['short_name'] ?? $user['full_name'] ?? 'Unknown',
                'assignee_id'     => $validated['assignee_id'],
                'assignee_name'   => $assignee['short_name'],
                'category'        => $validated['category'],
                'sub_category'    => $validated['sub_category'],
                'assigned_target' => $validated['assigned_target'],
                'unit'            => $validated['unit'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        return back()->with('success', 'Target linkage saved for ' . $assignee['short_name'] . '.');
    }

    public function destroy(string $id)
    {
        $user    = $this->currentUser();
        $linkage = $this->supabase->first('kpi_linkages', ['id' => 'eq.' . $id]);

        if (!$linkage || $linkage['assigner_id'] !== $user['id']) {
            return back()->with('error', 'Not authorized.');
        }

        $this->supabase->delete('kpi_linkages', ['id' => 'eq.' . $id]);
        return back()->with('success', 'Linkage removed.');
    }
}
