<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Shared "My Profile" page for every role. What can be viewed/edited is
 * gated by the user's own role rather than by route/middleware, since a
 * user is always acting on their own account here.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['department', 'studentProfile', 'counselorProfile']);

        return view('profile.edit', [
            'user' => $user,
            'recentActivity' => $user->notifications()->latest()->limit(6)->get(),
        ]);
    }

    /**
     * Update the self-editable contact/identity fields for the current role.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->update($data);

        $this->logAudit('profile.updated', $user, 'Updated their own profile details.');

        return back()->with('status', 'Your profile was updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->validated()['password']),
            'password_changed_at' => now(),
            'must_change_password' => false,
        ])->save();

        $this->logAudit('profile.password_changed', $user, 'Changed their own password.');

        return back()->with('status', 'Your password was updated.');
    }

    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar_path' => $path]);

        $this->logAudit('profile.avatar_updated', $user, 'Updated their own profile photo.');

        return back()->with('status', 'Your profile photo was updated.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return back()->with('status', 'Your profile photo was removed.');
    }

    protected function logAudit(string $action, User $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => $subject->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => User::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
