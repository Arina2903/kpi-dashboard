<?php

namespace App\Http\Controllers\Platform\Concerns;

use App\Services\SupabaseUserService;

/**
 * Where to send someone the moment they have a working Platform session —
 * shared between `AuthController::submitLogin()` (an ordinary password
 * login) and `InviteController::setPassword()` (finishing an invite or a
 * password reset). Both are "a session now exists, decide the landing page"
 * moments for the exact same account state, and used to disagree: only
 * `submitLogin()` got the role-aware fix, so someone accepting an invite
 * landed on the generic multi-company dashboard while logging in normally
 * with the identical account sent them straight to their actual workspace.
 *
 * A Richworks Super Admin lands on the multi-company overview (they're meant
 * to see everything). Anyone else with exactly one active company
 * membership -- the common case -- skips straight into that company's own
 * workspace: a Company Admin to the Departments page
 * (DepartmentController::ensureCompanyAccess() requires that role), anyone
 * below that to their own department's submissions page
 * (KpiSubmissionController — their actual day-to-day workspace) when they
 * belong to exactly one department, falling back to the company's KPI list
 * (open to any member) when that's ambiguous. Anyone with zero or multiple
 * company memberships lands on the dashboard, same as before, since there's
 * no single obvious company to jump into.
 */
trait ResolvesLandingUrl
{
    protected function landingUrlFor(string $accessToken): string
    {
        $supabase = new SupabaseUserService($accessToken);

        // Filtered on the token's own `sub` claim — an unfiltered lookup can
        // return a DIFFERENT authorized user's row here (a Super Admin or
        // Company Admin can see other people's rows under RLS), sending
        // someone to another user's landing page. See
        // SupabaseUserService::currentAuthUserId()'s docblock.
        $me = $supabase->first('users', [
            'auth_user_id' => 'eq.' . $supabase->currentAuthUserId(),
            'select' => 'id,role',
        ]);

        // Both platform tiers land on the multi-company overview: a Super
        // Admin because they see everything, a Platform Admin because their
        // assigned companies are exactly what that page lists for them.
        if (!$me || in_array($me['role'], ['richworks_super_admin', 'platform_admin'], true)) {
            return route('platform.dashboard');
        }

        $memberships = $supabase->get('company_users', [
            'user_id' => 'eq.' . $me['id'],
            'status' => 'eq.active',
            'select' => 'company_id,role',
        ]);

        if (count($memberships) !== 1) {
            return route('platform.dashboard');
        }

        $companyId = $memberships[0]['company_id'];

        if ($memberships[0]['role'] === 'company_admin') {
            return route('platform.departments.index', ['company' => $companyId]);
        }

        // SLT has company-wide visibility but no department of their own to
        // drop into, so the company's KPI list is their actual workspace.
        if ($memberships[0]['role'] === 'slt') {
            return route('platform.kpis.index', ['company' => $companyId]);
        }

        $departmentMemberships = $supabase->get('department_users', [
            'user_id' => 'eq.' . $me['id'],
            'select' => 'department_id',
        ]);

        if (count($departmentMemberships) === 1) {
            return route('platform.submissions.index', [
                'company' => $companyId,
                'department' => $departmentMemberships[0]['department_id'],
            ]);
        }

        return route('platform.kpis.index', ['company' => $companyId]);
    }
}
