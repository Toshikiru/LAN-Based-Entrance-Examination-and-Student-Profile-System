<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks an authenticated user to the "My Profile" page after their password
 * was reset for them (see resetPassword() on AdministratorController /
 * StudentController), until they set their own new password there.
 */
class EnsurePasswordIsCurrent
{
    /**
     * Route names left reachable while a password change is pending —
     * just enough to view/update the profile page and to sign out.
     */
    protected array $allowedRouteNames = [
        'profile.edit',
        'profile.update',
        'profile.password',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! in_array($request->route()?->getName(), $this->allowedRouteNames, true)) {
            return redirect()
                ->route('profile.edit')
                ->with('warning', 'Your password was reset by a Guidance Administrator. Please set a new password before continuing.');
        }

        return $next($request);
    }
}
