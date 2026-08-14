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
 * Richworks Super Admin only. Every read/write here goes through the
 * caller's own `SupabaseUserService` (their real Supabase Auth token) — never
 * service_role — so it is RLS, not this controller, that actually enforces
 * "only a Super Admin may do this." `store()`/`storeAdmin()` will simply fail
 * with a 403 from Postgres if the caller somehow isn't one.
 */
class CompanyController extends Controller
{
    use LogsAdminActions;

    /**
     * RLS already stops a non-Super-Admin from reading/writing another
     * company's data — but this page itself, and the privileged
     * createUser() call inside storeAdmin(), have no reason to run for
     * anyone but a Super Admin at all. Checking here is defense-in-depth,
     * not a substitute for the database-level checks.
     */
    private function ensureSuperAdmin(Request $request): void
    {
        $platformUser = $request->attributes->get('platformUser');

        abort_unless($platformUser['is_super_admin'] ?? false, 403, 'Richworks Super Admin access required.');
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $companies = $supabase->get('companies', [
            'select' => '*',
            'order' => 'created_at.desc',
        ]);

        $companyIds = array_column($companies, 'id');

        $admins = empty($companyIds)
            ? []
            : $supabase->get('company_users', [
                'company_id' => 'in.(' . implode(',', $companyIds) . ')',
                'role' => 'eq.company_admin',
                'select' => 'company_id,users(name,email)',
            ]);

        return Inertia::render('Platform/Companies/Index', [
            'companies' => $companies,
            'admins' => $admins,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $newCompany = $supabase->insert('companies', [
                'name' => $request->name,
                'code' => strtoupper($request->code),
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not create company: ' . $e->getMessage());
        }

        try {
            $this->logAdminAction($request, 'create_company', $newCompany[0]['id'], null, [
                'name' => $request->name,
                'code' => $newCompany[0]['code'],
            ]);
        } catch (\Throwable) {
            return back()->with('error', 'Company was created, but the action could not be logged — contact support before continuing.');
        }

        return back()->with('success', 'Company "' . $request->name . '" created.');
    }

    /**
     * Invites the first Company Admin for a company. Creating the Supabase
     * Auth user requires service_role (a genuinely privileged "invite"
     * operation — see SupabaseAuthService), but linking them to the company
     * via `company_users` still goes through the caller's own RLS-scoped
     * token, so a non-Super-Admin calling this endpoint gets rejected by
     * Postgres on that second step even if the first step succeeded.
     *
     * Phase 11: no password is generated here at all — generateInviteLink()
     * mints a token we email a link around, and the invitee chooses their
     * own password when they accept it (InviteController::setPassword()).
     */
    public function storeAdmin(Request $request, string $company, SupabaseAuthService $auth, SupabaseService $privileged)
    {
        $this->ensureSuperAdmin($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        try {
            $invite = $auth->generateInviteLink($request->email, [
                'name' => $request->name,
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not create the admin account: ' . $e->getMessage());
        }

        $newUserId = $invite['user']['id'] ?? null;

        if (!$newUserId) {
            return back()->with('error', 'Invite was created but the new account id was missing — try again.');
        }

        // Looked up via the privileged client, not the caller's own RLS-scoped
        // one: `users_select` only lets a caller see people already linked to
        // one of their companies — which this brand-new user isn't, yet. The
        // ensureSuperAdmin() check above is what actually authorizes this.
        // generateInviteLink() already gives us the id synchronously; this
        // wait is only for the `on_auth_user_created` trigger to finish
        // writing the matching `public.users` row `company_users` needs.
        $newUser = $privileged->firstEventually('users', [
            'id' => 'eq.' . $newUserId,
            'select' => 'id',
        ]);

        if (!$newUser) {
            return back()->with('error', 'Admin account was created but its profile row never appeared — try linking them again in a moment.');
        }

        try {
            $supabase->insert('company_users', [
                'company_id' => $company,
                'user_id' => $newUser['id'],
                'role' => 'company_admin',
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Admin account was created but could not be linked to the company: ' . $e->getMessage());
        }

        try {
            $this->logAdminAction($request, 'invite_company_admin', $company, $newUser['id'], [
                'email' => $request->email,
            ]);
        } catch (\Throwable) {
            return back()->with('error', 'Admin was created and linked, but the action could not be logged — contact support before continuing.');
        }

        $companyRow = $supabase->first('companies', [
            'id' => 'eq.' . $company,
            'select' => 'name',
        ]);

        try {
            Mail::to($request->email)->send(new PlatformInviteMail(
                route('platform.invite.accept', ['token' => $invite['hashed_token']]),
                $request->name,
                $companyRow['name'] ?? 'Performix',
                'a Company Admin',
            ));
        } catch (\Throwable $e) {
            return back()->with('error', 'Admin was created and linked, but the invite email could not be sent: ' . $e->getMessage());
        }

        return back()->with('success', "Invite sent to {$request->email}.");
    }
}