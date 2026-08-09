<?php

namespace App\Http\Requests\Profile;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the profile fields a user is allowed to self-edit. Which fields
 * apply is decided by role — Super Admins have no other UI for managing
 * their own account, so they may update their name; Counselors and Students
 * have their name/identity records managed elsewhere (by a Super Admin or
 * Counselor respectively) and may only update their contact email here.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        $rules = [
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
        ];

        if ($this->user()->role === UserRole::SuperAdmin) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}
