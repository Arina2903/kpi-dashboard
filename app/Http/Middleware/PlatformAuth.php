<?php

namespace App\Http\Middleware;

use App\Services\SupabaseUserService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the multi-company platform's routes. Unlike `KpiAuth` (which trusts
 * whatever the session says), this middleware re-resolves the caller's
 * identity and role on every request via `SupabaseUserService` — using their
 * own Supabase Auth token, so the answer comes from RLS, not from a session
 * value that could go stale the moment a role changes.
 */
class PlatformAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = session('platform_access_token');

        if (!$accessToken) {
            return redirect()
                ->route('platform.login')
                ->with('error', 'Please log in first.');
        }

        $supabase = new SupabaseUserService($accessToken);

        try {
            // Filtered explicitly on the token's own `sub` claim — NOT left
            // to RLS to narrow down on its own. RLS decides which rows are
            // visible (for a Super Admin, every row in the table; for a
            // Company Admin, every user in their own company), not which one
            // is the caller. Without this filter, `first()` silently returns
            // an arbitrary visible row instead of the caller's own.
            $me = $supabase->first('users', [
                'auth_user_id' => 'eq.' . $supabase->currentAuthUserId(),
                'select' => 'id,name,email,role,status',
            ]);
        } catch (\Throwable) {
            session()->forget(['platform_access_token', 'platform_refresh_token']);

            return redirect()
                ->route('platform.login')
                ->with('error', 'Your session has expired. Please log in again.');
        }

        if (!$me || $me['status'] !== 'active') {
            session()->forget(['platform_access_token', 'platform_refresh_token']);

            return redirect()
                ->route('platform.login')
                ->with('error', 'This account is disabled.');
        }

        // Two independent axes — see the role model in CLAUDE.md. The platform
        // tier says what someone is across companies; the company tier says
        // what they are inside one. A Platform Admin has no reach except the
        // companies explicitly assigned to them, which is why that list is
        // resolved here rather than inferred from a boolean.
        $isSuperAdmin = $me['role'] === 'richworks_super_admin';
        $isPlatformAdmin = $me['role'] === 'platform_admin';

        $assignedCompanyIds = $isPlatformAdmin
            ? collect($supabase->get('platform_admin_assignments', [
                'user_id' => 'eq.' . $me['id'],
                'select' => 'company_id',
            ]) ?? [])->pluck('company_id')->all()
            : [];

        $companyUsers = $isSuperAdmin
            ? []
            : $supabase->get('company_users', [
                'user_id' => 'eq.' . $me['id'],
                'status' => 'eq.active',
                'select' => 'company_id,role,companies(name,code)',
            ]);

        $request->attributes->set('platformUser', [
            'id' => $me['id'],
            'name' => $me['name'],
            'email' => $me['email'],
            'platform_role' => $me['role'],
            'is_super_admin' => $isSuperAdmin,
            'is_platform_admin' => $isPlatformAdmin,
            'assigned_company_ids' => $assignedCompanyIds,
            'company_memberships' => $companyUsers,
        ]);
        $request->attributes->set('platformSupabase', $supabase);

        return $next($request);
    }
}