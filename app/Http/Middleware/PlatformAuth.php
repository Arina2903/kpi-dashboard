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
            $me = $supabase->first('users', [
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

        $isSuperAdmin = $me['role'] === 'richworks_super_admin';

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
            'is_super_admin' => $isSuperAdmin,
            'company_memberships' => $companyUsers,
        ]);
        $request->attributes->set('platformSupabase', $supabase);

        return $next($request);
    }
}