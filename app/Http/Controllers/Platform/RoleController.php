<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\LogsAdminActions;
use App\Http\Controllers\Platform\Concerns\PlatformAuthorization;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;

/**
 * Per-department, per-company job-level roles (e.g. "Staff", "Lead") — the
 * self-service piece the platform was missing: which of these exist, and
 * how many tiers, is entirely up to each company. As with
 * DepartmentController, `ensureCompanyAdmin()` is a clean-redirect
 * convenience; `roles_write`'s RLS policy is what actually enforces it.
 */
class RoleController extends Controller
{
    use LogsAdminActions;
    use PlatformAuthorization;

    public function store(Request $request, string $company, string $department)
    {
        $this->ensureCompanyAdmin($request, $company);

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

        try {
            $this->logCompanyAction($request, 'create_role', $company, null, [
                'department_id' => $department,
                'label' => $request->label,
            ], 'role');
        } catch (\Throwable) {
            return back()->with('error', 'Role was created, but the action could not be logged — contact support before continuing.');
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
        $this->ensureCompanyAdmin($request, $company);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $roleBelongsToDepartment = $supabase->first('roles', [
            'id' => 'eq.' . $role,
            'department_id' => 'eq.' . $department,
            'select' => 'id,label',
        ]);

        if (!$roleBelongsToDepartment) {
            return back()->with('error', 'That role no longer belongs to this department.');
        }

        try {
            $supabase->delete('roles', ['id' => 'eq.' . $role]);
        } catch (\Throwable) {
            return back()->with('error', 'Could not remove this role — it may still be assigned to someone, or be the department\'s last remaining role.');
        }

        try {
            $this->logCompanyAction($request, 'delete_role', $company, null, [
                'department_id' => $department,
            ], 'role', $role, ['label' => $roleBelongsToDepartment['label']]);
        } catch (\Throwable) {
            return back()->with('error', 'Role was removed, but the action could not be logged — contact support before continuing.');
        }

        return back()->with('success', 'Role removed.');
    }
}
