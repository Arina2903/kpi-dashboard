<?php

namespace App\Console\Commands;

use App\Services\SupabaseService;
use Illuminate\Console\Command;

/**
 * Phase 12 dry run: reports what migrating a legacy company's `employees`
 * rows into the multi-company platform's `company_users`/`department_users`/
 * `roles` model would look like — without writing a single row anywhere.
 *
 * This exists because Phase 12 is a different kind of change from Phases
 * 8-11: those were additive (new tables/columns, nothing existing touched).
 * This one means taking real, live employee data out of the legacy
 * EXECUTIVE/MANAGER/VP/SLT model and mapping it onto the new one — real
 * accounts, real access, real production rows. Before any of that happens,
 * this command answers the questions a real migration script would
 * otherwise get wrong silently: how many people, what shape is the data
 * actually in, and which legacy roles don't map cleanly.
 *
 * Uses SupabaseService (service_role) rather than a signed-in user's token,
 * since this is an offline reporting tool with no caller session — same
 * justification as platform:bootstrap-super-admin's one-time service_role
 * use, except this command never writes.
 */
class PlanLegacyToPlatformMigration extends Command
{
    protected $signature = 'platform:plan-migration {company_code? : Limit to one legacy company_code (e.g. RCG). Omit to report on every company_code found in employees.}';

    protected $description = 'Read-only Phase 12 dry run: reports how legacy employees would map onto the multi-company platform model. Writes nothing.';

    /** The only four values ApprovalHierarchyService recognizes — anything else is a data-quality flag, not a mapping decision. */
    private const KNOWN_ROLES = ['EXECUTIVE', 'MANAGER', 'VP', 'SLT'];

    /**
     * Proposed, NOT final — MANAGER's tier in particular is a real open
     * question (department_user vs department_admin) that only Richworks
     * can settle, since it depends on whether managers should be able to
     * invite/manage their own department's people going forward.
     */
    private const PROPOSED_ROLE_MAP = [
        'EXECUTIVE' => ['company_users.role' => 'department_user', 'department_users.role' => 'department_user', 'roles.label' => 'Executive', 'roles.rank' => 0],
        'MANAGER'   => ['company_users.role' => 'department_user', 'department_users.role' => 'department_user', 'roles.label' => 'Manager', 'roles.rank' => 1, 'flag' => 'Could arguably be department_admin instead — confirm whether Managers should administer their own department under the new model.'],
        'VP'        => ['company_users.role' => 'department_user', 'department_users.role' => 'department_admin', 'roles.label' => 'VP', 'roles.rank' => 2],
        'SLT'       => ['company_users.role' => 'company_admin', 'department_users.role' => 'department_admin', 'roles.label' => 'SLT', 'roles.rank' => 3],
    ];

    public function handle(SupabaseService $supabase): int
    {
        $this->warn('DRY RUN ONLY — this command reads employees/companies/departments/roles and writes nothing.');
        $this->newLine();

        $companyCodeFilter = $this->argument('company_code');

        $employees = $this->fetchAllActiveEmployees($supabase, $companyCodeFilter);

        if (empty($employees)) {
            $this->error($companyCodeFilter
                ? "No active employees found for company_code={$companyCodeFilter}."
                : 'No active employees found at all.');

            return self::FAILURE;
        }

        $byCompany = collect($employees)->groupBy(fn ($e) => $e['company_code'] ?? '(missing)');

        $this->info(sprintf('Found %d active employee(s) across %d company_code value(s): %s',
            count($employees),
            $byCompany->count(),
            $byCompany->keys()->implode(', ')
        ));
        $this->newLine();

        foreach ($byCompany as $companyCode => $companyEmployees) {
            $this->reportCompany($supabase, $companyCode, $companyEmployees->all());
        }

        $this->reportRoleMappingProposal();
        $this->reportDataQuality($employees);

        $this->newLine();
        $this->warn('Nothing was written. This is a report to review — not a migration.');

        return self::SUCCESS;
    }

    /**
     * PostgREST caps rows per response (commonly 1000) — a company with
     * 500-1,000+ users, times three known company codes, can exceed that in
     * one request. Pages explicitly rather than trusting a single get() to
     * return everything.
     */
    private function fetchAllActiveEmployees(SupabaseService $supabase, ?string $companyCode): array
    {
        $pageSize = 1000;
        $offset = 0;
        $all = [];

        $filters = ['is_active' => 'eq.true', 'select' => 'id,employee_id,short_name,full_name,email,role,company_code,department_code,department,manager_id,vp_id,reports_to_id,user_id'];

        if ($companyCode) {
            $filters['company_code'] = 'eq.' . $companyCode;
        }

        while (true) {
            $page = $supabase->get('employees', $filters + ['limit' => $pageSize, 'offset' => $offset]);

            if (empty($page)) {
                break;
            }

            array_push($all, ...$page);

            if (count($page) < $pageSize) {
                break;
            }

            $offset += $pageSize;
        }

        return $all;
    }

    private function reportCompany(SupabaseService $supabase, string $companyCode, array $employees): void
    {
        $this->line("<fg=cyan;options=bold>=== {$companyCode} ===</>");

        $companyRow = $companyCode !== '(missing)'
            ? $supabase->first('companies', ['code' => 'eq.' . $companyCode, 'select' => 'id,name,code'])
            : null;

        $this->line($companyRow
            ? "  companies row: found — id={$companyRow['id']}, name=\"{$companyRow['name']}\""
            : '  companies row: NOT FOUND for this code — a companies row must exist before any company_users/roles rows can reference it.');

        $byDepartment = collect($employees)->groupBy(fn ($e) => $e['department_code'] ?? '(missing)');

        $rows = [];

        foreach ($byDepartment as $departmentCode => $deptEmployees) {
            $departmentRow = ($companyRow && $departmentCode !== '(missing)')
                ? $supabase->first('departments', [
                    'company_code' => 'eq.' . $companyCode,
                    'code' => 'eq.' . $departmentCode,
                    'select' => 'id,company_id,name,code',
                ])
                : null;

            $rolesExisting = $departmentRow
                ? count($supabase->get('roles', ['department_id' => 'eq.' . $departmentRow['id'], 'select' => 'id']) ?? [])
                : 0;

            $byRole = collect($deptEmployees)->countBy(fn ($e) => strtoupper(trim($e['role'] ?? '')) ?: '(missing)');

            $rows[] = [
                $departmentCode,
                $departmentRow ? 'yes' : 'no',
                $departmentRow ? ($departmentRow['company_id'] ? 'yes' : 'no (needs backfill)') : '—',
                $rolesExisting,
                count($deptEmployees),
                $byRole->map(fn ($count, $role) => "{$role}:{$count}")->implode(', '),
            ];
        }

        $this->table(
            ['department_code', 'departments row exists', 'has company_id (new FK)', 'existing roles rows', 'active employees', 'role breakdown'],
            $rows
        );

        if ($companyCode === 'RCG' || collect($employees)->contains(fn ($e) => ($e['department_code'] ?? null) === 'BTS')) {
            $this->comment('  Note: this company has employees in department_code=BTS — a cross-department support carve-out in the legacy model (see KpiController::sidebarData()). The new model has no equivalent short of richworks_super_admin (which is company-crossing, not department-crossing within one company). This needs an explicit decision before migrating BTS members — see the open questions below.');
        }

        $this->newLine();
    }

    private function reportRoleMappingProposal(): void
    {
        $this->line('<fg=cyan;options=bold>=== Proposed legacy role -> platform mapping (NOT applied — confirm before writing) ===</>');

        $rows = [];
        foreach (self::PROPOSED_ROLE_MAP as $legacyRole => $mapping) {
            $rows[] = [
                $legacyRole,
                $mapping['company_users.role'],
                $mapping['department_users.role'],
                $mapping['roles.label'] . ' (rank ' . $mapping['roles.rank'] . ')',
                $mapping['flag'] ?? '',
            ];
        }

        $this->table(['legacy role', 'company_users.role', 'department_users.role', 'roles row', 'open question'], $rows);
    }

    private function reportDataQuality(array $employees): void
    {
        $this->line('<fg=cyan;options=bold>=== Data quality — blockers for a real migration ===</>');

        $missingEmail = collect($employees)->filter(fn ($e) => empty($e['email']));
        $emailCounts = collect($employees)->filter(fn ($e) => !empty($e['email']))->countBy(fn ($e) => strtolower(trim($e['email'])));
        $duplicateEmails = $emailCounts->filter(fn ($count) => $count > 1);
        $unknownRole = collect($employees)->filter(fn ($e) => !in_array(strtoupper(trim($e['role'] ?? '')), self::KNOWN_ROLES, true));
        $missingDepartment = collect($employees)->filter(fn ($e) => empty($e['department_code']));
        $alreadyLinked = collect($employees)->filter(fn ($e) => !empty($e['user_id']));

        $rows = [
            ['Missing email (blocks Supabase Auth account creation)', $missingEmail->count()],
            ['Duplicate email across active employees (Supabase Auth requires unique emails)', $duplicateEmails->count() . ' email(s) shared by ' . $duplicateEmails->sum() . ' row(s)'],
            ['Role outside EXECUTIVE/MANAGER/VP/SLT (no mapping defined above)', $unknownRole->count()],
            ['Missing department_code (nothing to attach a department_users row to)', $missingDepartment->count()],
            ['Already has employees.user_id set (already linked to a Supabase Auth account)', $alreadyLinked->count()],
        ];

        $this->table(['check', 'count'], $rows);

        if ($duplicateEmails->isNotEmpty()) {
            $this->warn('  Duplicate emails: ' . $duplicateEmails->keys()->implode(', '));
        }

        if ($alreadyLinked->isNotEmpty()) {
            $this->warn('  ' . $alreadyLinked->count() . ' employee(s) already have a user_id — find out what created that link before assuming this migration starts from zero.');
        }
    }
}
