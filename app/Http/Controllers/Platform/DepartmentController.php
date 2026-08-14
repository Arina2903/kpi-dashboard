<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\LogsAdminActions;
use App\Mail\PlatformInviteMail;
use App\Services\SupabaseAuthService;
use App\Services\SupabaseService;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

/**
 * Department management, scoped to one company at a time (the URL's
 * {company} segment). As with CompanyController, every read/write goes
 * through the caller's own RLS-scoped token — the app-level check in
 * `ensureCompanyAccess()` exists so a non-member never even loads this page,
 * not because RLS needs the help to keep data safe.
 */
class DepartmentController extends Controller
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

    public function index(Request $request, string $company)
    {
        $this->ensureCompanyAccess($request, $company);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $companyRow = $supabase->first('companies', [
            'id' => 'eq.' . $company,
            'select' => 'id,name,code',
        ]);

        $departments = $supabase->get('departments', [
            'company_id' => 'eq.' . $company,
            'select' => '*',
            'order' => 'created_at.desc',
        ]);

        $departmentIds = array_column($departments, 'id');

        $members = empty($departmentIds)
            ? []
            : $supabase->get('department_users', [
                'department_id' => 'in.(' . implode(',', $departmentIds) . ')',
                'select' => 'department_id,role,role_id,users(name,email)',
            ]);

        $roles = empty($departmentIds)
            ? []
            : $supabase->get('roles', [
                'department_id' => 'in.(' . implode(',', $departmentIds) . ')',
                'select' => '*',
                'order' => 'rank.asc',
            ]);

        return Inertia::render('Platform/Departments/Index', [
            'company' => $companyRow,
            'departments' => $departments,
            'members' => $members,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request, string $company)
    {
        $this->ensureCompanyAccess($request, $company);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $newDepartment = $supabase->insert('departments', [
                'company_id' => $company,
                'name' => $request->name,
                'code' => strtoupper($request->code),
            ]);

            // Guardrail: a department with zero roles has nothing to assign
            // new members to. Seeding one default role keeps self-service
            // unblocked — a company that wants a "Lead" tier adds it
            // afterward; one that doesn't can leave this as-is forever.
            $supabase->insert('roles', [
                'department_id' => $newDepartment[0]['id'],
                'label' => 'Member',
                'rank' => 0,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not create department: ' . $e->getMessage());
        }

        if ($logFailure = $this->logIfSuperAdmin($request, 'create_department', $company, ['name' => $request->name])) {
            return $logFailure;
        }

        return back()->with('success', 'Department "' . $request->name . '" created.');
    }

    /**
     * Invites a Department Admin or Department User. Two links are created
     * beyond the auth account itself: a `company_users` row (so they count as
     * a member of the company at all) and a `department_users` row (so they
     * are scoped to this specific department). Both go through the caller's
     * own token — a Company Admin inviting someone into a department they
     * don't actually own gets rejected by RLS on the `departments` lookup
     * inside `department_users_insert`, not by anything in this method.
     */
    public function storeUser(Request $request, string $company, string $department, SupabaseAuthService $auth, SupabaseService $privileged)
    {
        $this->ensureCompanyAccess($request, $company);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role' => 'required|in:department_admin,department_user',
            'role_id' => 'required|uuid',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        // role_id is the company's own configurable job-level role (§3 of the
        // blueprint) — confirm it actually belongs to this department rather
        // than trusting a client-supplied id blindly. RLS would reject the
        // department_users insert below regardless, but this gives a clean
        // error instead of a raw constraint failure.
        $roleBelongsToDepartment = $supabase->first('roles', [
            'id' => 'eq.' . $request->role_id,
            'department_id' => 'eq.' . $department,
            'select' => 'id',
        ]);

        if (!$roleBelongsToDepartment) {
            return back()->withInput()->with('error', 'Choose a role that belongs to this department.');
        }

        // Phase 11: as in CompanyController::storeAdmin(), no password is
        // generated here — the invitee sets their own when they accept.
        try {
            $invite = $auth->generateInviteLink($request->email, [
                'name' => $request->name,
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not create the account: ' . $e->getMessage());
        }

        $newUserId = $invite['user']['id'] ?? null;

        if (!$newUserId) {
            return back()->with('error', 'Invite was created but the new account id was missing — try again.');
        }

        // Looked up via the privileged client, not the caller's own RLS-scoped
        // one: `users_select` only lets a caller see people already linked to
        // one of their companies — which this brand-new user isn't, yet. The
        // ensureCompanyAccess() check above is what actually authorizes this.
        // generateInviteLink() already gives us the id synchronously; this
        // wait is only for the `on_auth_user_created` trigger to finish
        // writing the matching `public.users` row `company_users` needs.
        $newUser = $privileged->firstEventually('users', [
            'id' => 'eq.' . $newUserId,
            'select' => 'id',
        ]);

        if (!$newUser) {
            return back()->with('error', 'Account was created but its profile row never appeared — try adding them again in a moment.');
        }

        try {
            $supabase->insert('company_users', [
                'company_id' => $company,
                'user_id' => $newUser['id'],
                'role' => $request->role,
            ]);

            $supabase->insert('department_users', [
                'department_id' => $department,
                'user_id' => $newUser['id'],
                'role' => $request->role,
                'role_id' => $request->role_id,
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Account was created but could not be linked to the department: ' . $e->getMessage());
        }

        if ($logFailure = $this->logIfSuperAdmin($request, 'invite_department_user', $company, [
            'department_id' => $department,
            'email' => $request->email,
            'role' => $request->role,
        ])) {
            return $logFailure;
        }

        $companyRow = $supabase->first('companies', [
            'id' => 'eq.' . $company,
            'select' => 'name',
        ]);

        $roleLabel = $request->role === 'department_admin' ? 'a Department Admin' : 'a Department User';

        try {
            Mail::to($request->email)->send(new PlatformInviteMail(
                route('platform.invite.accept', ['token' => $invite['hashed_token']]),
                $request->name,
                $companyRow['name'] ?? 'Performix',
                $roleLabel,
            ));
        } catch (\Throwable $e) {
            return back()->with('error', 'Account was created and linked, but the invite email could not be sent: ' . $e->getMessage());
        }

        return back()->with('success', "Invite sent to {$request->email}.");
    }
}