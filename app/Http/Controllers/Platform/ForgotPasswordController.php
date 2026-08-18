<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\PlatformPasswordResetMail;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

/**
 * "Forgot password" for the multi-company Platform (Supabase Auth), separate
 * from the legacy app's own forgot-password flow. Mirrors InviteController's
 * shape closely -- both mint a Supabase Auth token server-side, email a link
 * we control, and land on the same Platform/Invite/SetPassword page to
 * finish. sendResetLink() always returns the same response regardless of
 * whether the email exists, so this endpoint can't be used to enumerate
 * registered accounts.
 */
class ForgotPasswordController extends Controller
{
    public function show()
    {
        return Inertia::render('Platform/ForgotPassword');
    }

    public function sendResetLink(Request $request, SupabaseAuthService $auth)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $link = $auth->generateRecoveryLink($request->email);

            Mail::to($request->email)->send(new PlatformPasswordResetMail(
                route('platform.reset-password.accept', ['token' => $link['hashed_token']]),
            ));
        } catch (\Throwable $e) {
            // Deliberately swallowed -- a nonexistent email fails the same
            // way as a real mail-send error, so the response never reveals
            // which one happened.
            Log::info('Platform password reset request did not result in an email being sent', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'If an account exists for that email, a password reset link has been sent.');
    }

    /**
     * Verifies the emailed token, same as InviteController::accept() but
     * type=recovery. Reuses the same session key and Set Password page --
     * from this point on, resetting a password and finishing an invite are
     * the same "you have a temporary Supabase session, choose a password"
     * step.
     */
    public function accept(Request $request, SupabaseAuthService $auth)
    {
        $request->validate(['token' => 'required|string']);

        try {
            $session = $auth->verifyToken($request->query('token'), 'recovery');
        } catch (\Throwable) {
            return redirect()
                ->route('platform.forgot-password')
                ->with('error', 'This reset link is invalid or has expired — request a new one below.');
        }

        session(['platform_invite_access_token' => $session['access_token']]);

        return Inertia::render('Platform/Invite/SetPassword', [
            'email' => $session['user']['email'] ?? null,
        ]);
    }
}
