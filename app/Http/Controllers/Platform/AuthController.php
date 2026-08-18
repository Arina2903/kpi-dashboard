<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\ResolvesLandingUrl;
use App\Services\AuditLogService;
use App\Services\SupabaseAuthService;
use App\Services\SupabaseUserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuthController extends Controller
{
    use ResolvesLandingUrl;

    public function showLogin()
    {
        return Inertia::render('Platform/Login');
    }

    public function submitLogin(Request $request, SupabaseAuthService $auth, AuditLogService $auditLog)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $session = $auth->signIn($request->email, $request->password);
        } catch (\Throwable) {
            // Best-effort and not gated on `platformUser` (there isn't one
            // yet) — `actor_email` is the only identity a failed attempt has.
            // This must never block the login response on a logging hiccup,
            // and must never let a logging failure turn a wrong password
            // into a 500 instead of the same "invalid email or password"
            // response an attacker would see either way.
            $auditLog->recordBestEffort([
                'actor_email' => $request->email,
                'action' => 'login_failed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Invalid email or password.');
        }

        // Regenerated before the new session data is trusted — otherwise an
        // attacker who fixed this session id before login (a shared
        // subdomain, cookie-tossing, or a stray non-HttpOnly write
        // elsewhere) inherits platform_access_token the moment login
        // succeeds, since the session id itself never changed.
        $request->session()->regenerate();

        session([
            'platform_access_token' => $session['access_token'],
            'platform_refresh_token' => $session['refresh_token'] ?? null,
        ]);

        try {
            $me = (new SupabaseUserService($session['access_token']))->first('users', [
                'auth_user_id' => 'eq.' . $session['user']['id'],
                'select' => 'id',
            ]);

            $auditLog->recordBestEffort([
                'actor_user_id' => $me['id'] ?? null,
                'actor_email' => $request->email,
                'action' => 'login',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable) {
            // Best-effort — a successful login must never fail here.
        }

        return redirect()->to($this->landingUrlFor($session['access_token']));
    }

    public function logout(Request $request, SupabaseAuthService $auth, AuditLogService $auditLog)
    {
        $token = session('platform_access_token');

        if ($token) {
            try {
                $ownScope = new SupabaseUserService($token);
                $me = $ownScope->first('users', [
                    'auth_user_id' => 'eq.' . $ownScope->currentAuthUserId(),
                    'select' => 'id,email',
                ]);

                $auditLog->recordBestEffort([
                    'actor_user_id' => $me['id'] ?? null,
                    'actor_email' => $me['email'] ?? null,
                    'action' => 'logout',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable) {
                // Best-effort — logging out must succeed regardless.
            }

            try {
                $auth->signOut($token);
            } catch (\Throwable) {
                // Best-effort — the local session is cleared regardless below.
            }
        }

        session()->forget(['platform_access_token', 'platform_refresh_token']);

        return redirect()
            ->route('platform.login')
            ->with('success', 'You have been logged out.');
    }
}