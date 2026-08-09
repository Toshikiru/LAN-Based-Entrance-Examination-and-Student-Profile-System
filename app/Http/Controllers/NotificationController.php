<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Role-agnostic — every authenticated user (Super Admin, Counselor, Student)
 * manages their own notifications the same way.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->first()?->markAsRead();

        $redirect = (string) $request->input('redirect');

        // Only ever follow a same-origin redirect target — the value comes from a
        // hidden form field, which a user could tamper with client-side.
        if ($redirect === '' || ! str_starts_with($redirect, url('/'))) {
            $redirect = route('notifications.index');
        }

        return redirect($redirect);
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
