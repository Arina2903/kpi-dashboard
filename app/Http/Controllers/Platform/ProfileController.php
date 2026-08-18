<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Self-service profile for the multi-company Platform — the "user profile /
 * role detection / organization detection" piece of Phase 4 that had no
 * screen of its own yet. `platformUser` is already fully assembled by
 * PlatformAuth on every request (id, name, email, is_super_admin,
 * company_memberships with each membership's role and company name/code),
 * so this controller has nothing left to fetch.
 */
class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->attributes->get('platformUser');
        $supabase = $request->attributes->get('platformSupabase');

        // Filtered explicitly on PlatformAuth's already-resolved id, not left
        // unfiltered — a Super Admin or Company Admin can see other users'
        // rows under RLS, so an unfiltered lookup here isn't guaranteed to be
        // "yourself." See SupabaseUserService::currentAuthUserId()'s docblock.
        $telegram = $supabase->first('users', [
            'id' => 'eq.' . $me['id'],
            'select' => 'telegram_username,telegram_linked_at',
        ]);

        return Inertia::render('Platform/Profile', [
            'me' => $me,
            'telegram' => [
                'linked' => !empty($telegram['telegram_linked_at']),
                'username' => $telegram['telegram_username'] ?? null,
            ],
        ]);
    }

    public function updatePassword(Request $request, SupabaseAuthService $auth)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $auth->setPassword(session('platform_access_token'), $request->password);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not update your password: ' . $e->getMessage());
        }

        return back()->with('success', 'Password updated.');
    }
}
