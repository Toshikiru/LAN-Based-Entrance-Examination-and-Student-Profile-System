<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // `mimes:` alone (no separate `image` rule) — Laravel's `image` rule
            // does its own stricter getimagesize()-based check that can reject
            // valid SVGs, which would otherwise fail silently since the form
            // couldn't show why (fixed alongside this — see the settings view).
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,svg', 'max:512'],
        ];
    }

    public function attributes(): array
    {
        return [
            'logo' => 'school logo',
            'favicon' => 'browser favicon',
        ];
    }
}
