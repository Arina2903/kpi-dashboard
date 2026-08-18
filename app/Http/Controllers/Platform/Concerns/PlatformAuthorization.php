<?php

namespace App\Http\Controllers\Platform\Concerns;

use Illuminate\Http\Request;

/**
 * The access checks nearly every Platform controller needed, each copy-pasted
 * verbatim across a growing number of files instead of living once — confirmed
 * identical (down to the error message) in CompanyController,
 * ImportController, UserCreationController, KpiTemplateController, and
 * AuditLogController for `ensureSuperAdmin()`; in DepartmentController,
 * RoleController, and OnboardingController for the "company_admin of this
 * specific company" check (three different method names,
 * `ensureCompanyAccess()`/`ensureCompanyAdmin()`, one identical body); and in
 * KpiController for the "any member of this company" check.
 *
 * None of these enforce anything on their own — every one of them mirrors an
 * RLS policy that's the real boundary (`companies_insert`,
 * `company_users_insert`, `kpi_categories_insert`, etc., nearly all now shaped
 * `auth_can_administer_company(company_id)`). This trait exists so the
 * app-level pre-check (a clean redirect instead of a raw Postgres error) has
 * one place to live and change, not five-plus.
 *
 * ROLE MODEL (see CLAUDE.md for the full table). Two independent axes:
 *
 *   platform tier (users.role)      richworks_super_admin | platform_admin | member
 *   company tier (company_users)    company_admin | slt | executive | employee
 *
 * A Platform Admin is NOT a weaker Super Admin — it has no implicit reach at
 * all. Every company it can touch is an explicit `platform_admin_assignments`
 * row, which is why `canAdministerCompany()` checks that list rather than a
 * boolean.
 */
trait PlatformAuthorization
{
    /**
     * Center only. Reserved for genuinely platform-wide actions: creating and
     * deleting companies, granting platform access, curating the shared KPI
     * template library, reading the cross-company audit log.
     */
    protected function ensureSuperAdmin(Request $request): void
    {
        $platformUser = $request->attributes->get('platformUser');

        abort_unless($platformUser['is_super_admin'] ?? false, 403, 'Richworks Super Admin access required.');
    }

    /**
     * Any platform operator — Super Admin, or a Platform Admin acting within
     * an assigned company. Use this for Center-side operations that are
     * per-company rather than platform-wide (onboarding, imports, activation).
     */
    protected function ensurePlatformOperator(Request $request, string $company): void
    {
        $platformUser = $request->attributes->get('platformUser');

        if ($platformUser['is_super_admin'] ?? false) {
            return;
        }

        abort_unless(
            ($platformUser['is_platform_admin'] ?? false)
                && in_array($company, $platformUser['assigned_company_ids'] ?? [], true),
            403,
            'You are not assigned to operate this company.'
        );
    }

    /**
     * May administer this specific company: Super Admin, an assigned Platform
     * Admin, or the company's own Company Admin. Mirrors the
     * `auth_can_administer_company()` SQL predicate exactly — if you change
     * one, change the other.
     */
    protected function ensureCompanyAdmin(Request $request, string $company): void
    {
        abort_unless($this->canAdministerCompany($request, $company), 403, 'You are not an admin of this company.');
    }

    /**
     * Company-wide read: everyone above, plus SLT. Mirrors
     * `auth_can_view_company_wide()`.
     */
    protected function ensureCompanyWideViewer(Request $request, string $company): void
    {
        if ($this->canAdministerCompany($request, $company)) {
            return;
        }

        abort_unless(
            $this->companyRole($request, $company) === 'slt',
            403,
            'You do not have company-wide access.'
        );
    }

    protected function ensureCompanyMember(Request $request, string $company): void
    {
        $platformUser = $request->attributes->get('platformUser');

        if (($platformUser['is_super_admin'] ?? false) || $this->canAdministerCompany($request, $company)) {
            return;
        }

        $isMember = collect($platformUser['company_memberships'] ?? [])
            ->contains(fn ($m) => $m['company_id'] === $company);

        abort_unless($isMember, 403, 'You are not a member of this company.');
    }

    protected function canAdministerCompany(Request $request, string $company): bool
    {
        $platformUser = $request->attributes->get('platformUser');

        if ($platformUser['is_super_admin'] ?? false) {
            return true;
        }

        if (($platformUser['is_platform_admin'] ?? false)
            && in_array($company, $platformUser['assigned_company_ids'] ?? [], true)) {
            return true;
        }

        return $this->companyRole($request, $company) === 'company_admin';
    }

    /**
     * The caller's company tier in one company, or null if they have none.
     */
    protected function companyRole(Request $request, string $company): ?string
    {
        $platformUser = $request->attributes->get('platformUser');

        return collect($platformUser['company_memberships'] ?? [])
            ->firstWhere('company_id', $company)['role'] ?? null;
    }
}
