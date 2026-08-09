<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Show the "Forgot password?" page. This system is LAN-based and mail
     * usually isn't configured, so the primary path is always "contact the
     * Guidance Office" — the email form only appears when a real mailer is
     * set up (see mailIsConfigured()), never as a hard dependency.
     */
    public function forgotPassword(): View
    {
        return view('auth.forgot-password', [
            'mailConfigured' => $this->mailIsConfigured(),
        ]);
    }

    /**
     * Email a password reset link, using Laravel's standard password broker.
     * Only reachable when a real mailer is configured (see the route).
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show the "set a new password" form reached from the emailed link.
     */
    public function reset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Complete the reset using the emailed token, via the standard broker.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'password_changed_at' => now(),
                    'must_change_password' => false,
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Heuristic for "is SMTP actually set up": the no-op log/array drivers
     * mean nothing will ever be delivered, so there's no point offering the
     * email form in that case. Anything else (smtp, sendmail, etc.) is
     * treated as configured — no code change needed once .env is filled in.
     */
    protected function mailIsConfigured(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array', null], true);
    }
}
