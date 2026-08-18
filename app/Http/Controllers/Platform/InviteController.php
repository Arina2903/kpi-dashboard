<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\ResolvesLandingUrl;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Accepts an invite sent by CompanyController::storeAdmin() or
 * DepartmentController::storeUser() — both of which mint the token via
 * SupabaseAuthService::generateInviteLink() and email a link here rather
 * than generating a password on the invitee's behalf. Everything here runs
 * unauthenticated, on purpose: whoever holds the link *is* the proof of
 * identity, same as any other email-based invite/reset flow.
 */
class InviteController extends Controller
{
    use ResolvesLandingUrl;

    public function accept(Request $request, SupabaseAuthService $auth)
    {
        $request->validate(['token' => 'required|string']);

        try {
            $session = $auth->verifyToken($request->query('token'), 'invite');
        } catch (\Throwable) {
            return redirect()
                ->route('platform.login')
                ->with('error', 'This invite link is invalid or has expired — ask whoever invited you to send a new one.');
        }

        // Held only long enough to set a password in the next request — not
        // the ongoing platform session yet, since the account isn't usable
        // until it has a real password.
        session(['platform_invite_access_token' => $session['access_token']]);

        return Inertia::render('Platform/Invite/SetPassword', [
            'email' => $session['user']['email'] ?? null,
        ]);
    }

    public function setPassword(Request $request, SupabaseAuthService $auth)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $accessToken = session('platform_invite_access_token');

        if (!$accessToken) {
            return redirect()
                ->route('platform.login')
                ->with('error', 'Your invite session has expired — ask for a new invite link.');
        }

        try {
            $auth->setPassword($accessToken, $request->password);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not set your password: ' . $e->getMessage());
        }

        session()->forget('platform_invite_access_token');

        // Same reasoning as AuthController::submitLogin() — regenerate
        // before the account's real session data is trusted under this
        // session id.
        $request->session()->regenerate();

        // The token verified above is a real Supabase session — promoting it
        // straight to the ongoing platform session logs them in immediately
        // rather than sending them back to a login form they'd have to fill
        // in again seconds after choosing a password. Same role-aware
        // destination as an ordinary login (ResolvesLandingUrl) — this used
        // to always land on the dashboard regardless of role, landing a
        // Department Admin/User somewhere different than the exact same
        // account would reach by just logging in normally afterward.
        session(['platform_access_token' => $accessToken]);

        return redirect()->to($this->landingUrlFor($accessToken))->with('success', 'Password set — welcome to Performix.');
    }
}
