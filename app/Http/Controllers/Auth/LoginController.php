<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt and redirect to the role's dashboard.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(self::redirectPathFor($user->role));
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Resolve the dashboard route for the given role. Public/static so the
     * root route (routes/web.php) can also send an already-authenticated
     * visitor straight to their dashboard instead of bouncing them through
     * `/login`, which has nowhere else to send them back to — see the
     * comment on that route for why that specifically caused a redirect
     * loop.
     */
    public static function redirectPathFor(UserRole $role): string
    {
        return match ($role) {
            UserRole::SuperAdmin => route('admin.dashboard'),
            UserRole::Counselor => route('counselor.dashboard'),
            UserRole::Student => route('student.dashboard'),
        };
    }
}
