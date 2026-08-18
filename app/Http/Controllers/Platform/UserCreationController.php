<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\LogsAdminActions;
use App\Http\Controllers\Platform\Concerns\PlatformAuthorization;
use App\Mail\PlatformInviteMail;
use App\Services\SupabaseAuthService;
use App\Services\SupabaseService;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

/**
 * Performix Platform Blueprint, Phase 8: turns a Phase 7-staged Employees
 * import batch (`import_batches.rows`, status = 'validated') into real
 * accounts — spec section 17's "Create User Accounts" screen, deliberately
 * separate from the import step itself so the Center can create accounts
 * for all or only some staged employees rather than it happening
 * automatically the moment a file is confirmed.
 *
 * Every imported employee becomes a `department_user` — the Employees sheet
 * (Blueprint §8) has a "Position" column, not an access-tier column, so
 * there is nothing in the import to honestly justify granting
 * `department_admin` in bulk. Promoting someone afterward is a manual step
 * on the existing Departments page.
 *
 * Each staged row is tagged in place with `_account_created` (bool) and, on
 * failure, `_error` (string) as accounts are created — a batch only
 * advances to `status = 'completed'` once every row has an account or a
 * recorded failure, so creating "selected" employees now leaves the rest
 * visible to create later, and a failed row can be retried without
 * re-uploading anything.
 */
class UserCreationController extends Controller
{
    use LogsAdminActions;
    use PlatformAuthorization;

    private function findEmployeeBatch(SupabaseUserService $supabase, string $company, string $batch): ?array
    {
        return $supabase->first('import_batches', [
            'id' => 'eq.' . $batch,
            'company_id' => 'eq.' . $company,
            'type' => 'eq.employees',
            'select' => '*',
        ]);
    }

    public function show(Request $request, string $company, string $batch)
    {
        $this->ensureSuperAdmin($request);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $companyRow = $supabase->first('companies', ['id' => 'eq.' . $company, 'select' => 'id,name']);
        abort_if(!$companyRow, 404);

        $batchRow = $this->findEmployeeBatch($supabase, $company, $batch);
        abort_if(!$batchRow, 404);

        $rows = $batchRow['rows'] ?? [];
        $pending = array_values(array_filter($rows, fn ($r) => empty($r['_account_created'])));

        return Inertia::render('Platform/Import/CreateUsers', [
            'company' => $companyRow,
            'batch' => ['id' => $batchRow['id'], 'filename' => $batchRow['filename'], 'status' => $batchRow['status']],
            'pending' => $pending,
            'totalStaged' => count($rows),
        ]);
    }

    public function store(Request $request, string $company, string $batch, SupabaseAuthService $auth, SupabaseService $privileged)
    {
        $this->ensureSuperAdmin($request);

        $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'email',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $batchRow = $this->findEmployeeBatch($supabase, $company, $batch);

        if (!$batchRow) {
            return back()->with('error', 'Import batch not found.');
        }

        $companyRow = $supabase->first('companies', ['id' => 'eq.' . $company, 'select' => 'name']);
        $companyName = $companyRow['name'] ?? 'Performix';

        $rows = $batchRow['rows'] ?? [];
        $selectedEmails = array_flip(array_map('strtolower', $request->emails));

        // Departments were already committed by the same original import (or
        // already existed) — resolve code -> id fresh here rather than
        // trusting anything on the staged row, which only ever held the
        // department code as a plain string.
        $departments = collect($supabase->get('departments', [
            'company_id' => 'eq.' . $company,
            'select' => 'id,code',
        ]))->keyBy('code');

        // The department's own lowest-rank configurable role per department
        // (Phase 8 of the platform's own build — see 2026_08_13_120000) —
        // fetched once for every department this batch might touch, rather
        // than once per selected employee (an N+1: a batch of 50 employees
        // across 5 departments used to make 50 role lookups instead of 1).
        // Every department is guaranteed at least one role by
        // prevent_last_role_deletion, so a lookup is never null in practice.
        $neededDepartmentIds = collect($rows)
            ->filter(fn ($r) => empty($r['_account_created']) && isset($selectedEmails[strtolower($r['email'] ?? '')]))
            ->map(fn ($r) => $departments->get($r['department_code'])['id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $rolesByDepartment = $neededDepartmentIds->isEmpty()
            ? collect()
            : collect($supabase->get('roles', [
                'department_id' => 'in.(' . $neededDepartmentIds->implode(',') . ')',
                'select' => 'id,department_id',
                'order' => 'rank.asc',
            ]))->groupBy('department_id')->map(fn ($group) => $group->first());

        $created = 0;
        $failed = 0;

        foreach ($rows as &$row) {
            $email = strtolower($row['email'] ?? '');

            if (!empty($row['_account_created']) || !isset($selectedEmails[$email])) {
                continue;
            }

            $department = $departments->get($row['department_code']);

            if (!$department) {
                $row['_error'] = "Department \"{$row['department_code']}\" no longer exists.";
                $failed++;
                continue;
            }

            $role = $rolesByDepartment->get($department['id']);

            try {
                $invite = $auth->generateInviteLink($row['email'], ['name' => $row['name']]);
                // Supabase's generate_link response has `id` at the top
                // level, not nested under a `user` key — see the matching
                // fix and note in CompanyController::storeAdmin().
                $newUserId = $invite['id'] ?? null;

                if (!$newUserId) {
                    throw new \RuntimeException('Invite created but no account id was returned.');
                }

                // Looked up via the privileged client, not the caller's own
                // RLS-scoped one — same reasoning as CompanyController::storeAdmin():
                // `users_select` only lets a caller see people already linked
                // to one of their companies, which this brand-new user isn't yet.
                //
                // Filtered on `auth_user_id`, not `id` — `$newUserId` is
                // auth.users.id, stored on `public.users` as `auth_user_id`,
                // a separate column from `public.users.id`. See the note in
                // CompanyController::storeAdmin().
                $newUser = $privileged->firstEventually('users', ['auth_user_id' => 'eq.' . $newUserId, 'select' => 'id']);

                if (!$newUser) {
                    throw new \RuntimeException('Account was created but its profile row never appeared.');
                }

                $supabase->insert('company_users', [
                    'company_id' => $company,
                    'user_id' => $newUser['id'],
                    'role' => 'employee',
                ]);

                $supabase->insert('department_users', [
                    'department_id' => $department['id'],
                    'user_id' => $newUser['id'],
                    'role' => 'employee',
                    'role_id' => $role['id'] ?? null,
                ]);

                Mail::to($row['email'])->send(new PlatformInviteMail(
                    route('platform.invite.accept', ['token' => $invite['hashed_token']]),
                    $row['name'],
                    $companyName,
                    'a Department User',
                ));

                $row['_account_created'] = true;
                unset($row['_error']);
                $created++;
            } catch (\Throwable $e) {
                $row['_error'] = $e->getMessage();
                $failed++;
            }
        }
        unset($row);

        $allDone = collect($rows)->every(fn ($r) => !empty($r['_account_created']));

        try {
            $supabase->update('import_batches', ['id' => 'eq.' . $batch], [
                'rows' => $rows,
                'status' => $allDone ? 'completed' : 'validated',
                'completed_at' => $allDone ? now()->toISOString() : null,
            ]);
        } catch (\Throwable $e) {
            // Logged, not just flashed: the accounts created above are real
            // and already invited regardless of whether this write succeeds
            // — without a log entry, a failure here leaves no durable trail
            // that `$created` accounts actually exist beyond the one admin
            // who saw this flash message.
            Log::error('import_batches row update failed after user accounts already created', [
                'company_id' => $company,
                'batch_id' => $batch,
                'created' => $created,
                'failed' => $failed,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Accounts were processed, but the batch record could not be updated: ' . $e->getMessage());
        }

        try {
            $this->logAdminAction($request, 'create_user_accounts', $company, null, [
                'batch_id' => $batch,
                'created' => $created,
                'failed' => $failed,
            ]);
        } catch (\Throwable) {
            // Non-fatal — the accounts themselves already exist above.
        }

        return redirect()
            ->route('platform.import.users.show', ['company' => $company, 'batch' => $batch])
            ->with('success', "{$created} account(s) created." . ($failed > 0 ? " {$failed} failed — see the table below." : ''));
    }
}
