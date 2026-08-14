<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\LogsAdminActions;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;

/**
 * Per-department, per-company job-level roles (e.g. "Staff", "Lead") — the
 * self-service piece the platform was missing: which of these exist, and
 * how many tiers, is entirely up to each company. As with
 * DepartmentController, `ensureCompanyAccess()` is a clean-redirect
 * convenience; `roles_write`'s RLS policy is what actually enforces it.
 */
class RoleController extends Controller
{
    use LogsAdminActions;

    private function ensureCompanyAccess(Request $request, string $company): void
    {
        $platformUser = $request->attributes->get('platformUser');

        if ($platformUser['is_super_admin'] ?? false) {
            return;
        }

        $isCompanyAdmin = collect($platformUser['company_memberships'] ?? [])
            ->contains(fn ($m) => $m['company_id'] === $company && $m['role'] === 'company_admin');

        abort_unless($isCompanyAdmin, 403, 'You are not an admin of this company.');
    }

    public function store(Request $request, string $company, string $department)
    {
        $this->ensureCompanyAccess($request, $company);

        $request->validate([
            'label' => 'required|string|max:100',
            'rank' => 'nullable|integer|min:0|max:100',
            'is_department_admin' => 'nullable|boolean',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $supabase->insert('roles', [
                'department_id' => $department,
                'label' => $request->label,
                'rank' => $request->integer('rank', 0),
                'is_department_admin' => $request->boolean('is_department_admin'),
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not create role: ' . $e->getMessage());
        }

        if ($logFailure = $this->logIfSuperAdmin($request, 'create_role', $company, [
            'department_id' => $department,
            'label' => $request->label,
        ])) {
            return $logFailure;
        }

        return back()->with('success', 'Role "' . $request->label . '" created.');
    }

    /**
     * Deletion is stopped at the database, not here — two separate guards can
     * reject it: `department_users.role_id` references this table
     * `on delete restrict` (someone still holds the role), and the
     * `prevent_last_role_deletion` trigger (it's the department's only
     * remaining role). Both surface as a generic Postgres error, so the
     * message below covers either case rather than guessing which fired.
     */
    public function destroy(Request $request, string $company, string $department, string $role)
    {
        $this->ensureCompanyAccess($request, $company);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $roleBelongsToDepartment = $supabase->first('roles', [
            'id' => 'eq.' . $role,
            'department_id' => 'eq.' . $department,
            'select' => 'id',
        ]);

        if (!$roleBelongsToDepartment) {
            return back()->with('error', 'That role no longer belongs to this department.');
        }

        try {
            $supabase->delete('roles', ['id' => 'eq.' . $role]);
        } catch (\Throwable) {
            return back()->with('error', 'Could not remove this role — it may still be assigned to someone, or be the department\'s last remaining role.');
        }

        if ($logFailure = $this->logIfSuperAdmin($request, 'delete_role', $company, [
            'department_id' => $department,
            'role_id' => $role,
        ])) {
            return $logFailure;
        }

        return back()->with('success', 'Role removed.');
    }
}
