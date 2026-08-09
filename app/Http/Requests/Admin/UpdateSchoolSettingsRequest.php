<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:255'],
            'system_display_name' => ['required', 'string', 'max:255'],
            'system_full_name' => ['nullable', 'string', 'max:500'],
            'short_code' => ['required', 'string', 'max:50'],
            'official_email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'mailing_address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'school_name' => 'school name',
            'system_display_name' => 'system name',
            'system_full_name' => 'system full name',
            'short_code' => 'short code',
            'official_email' => 'official email',
            'phone_number' => 'phone number',
            'mailing_address' => 'mailing address',
        ];
    }
}
