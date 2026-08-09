<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string', 'in:super_admin,counselor,student'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials, then enforce that
     * the role picked on the login form actually matches the account's real
     * role. The role pills are just a UI convenience — this is the part
     * that actually stops a student account from signing in through the
     * Admin/Counselor option (or vice versa), since it's checked here
     * server-side regardless of what the client sent.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('school_id', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'school_id' => trans('auth.failed'),
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $selectedRole = UserRole::from($this->string('role')->value());

        if ($user->role !== $selectedRole) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'school_id' => "This account is registered as a {$user->role->label()}, not a {$selectedRole->label()}. Please select the correct role above and try again.",
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'school_id' => 'This account has been deactivated. Please contact the administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'school_id' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('school_id')).'|'.$this->ip());
    }
}
