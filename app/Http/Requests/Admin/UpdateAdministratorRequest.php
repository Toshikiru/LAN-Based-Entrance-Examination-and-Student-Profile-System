<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdministratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $adminId = $this->route('administrator')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'school_id' => ['required', 'string', 'max:50', Rule::unique('users', 'school_id')->ignore($adminId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($adminId)],
            'position_title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'School ID',
            'position_title' => 'position title',
        ];
    }
}
