<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateYearLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('year_levels', 'name')->ignore($this->route('yearLevel')->id)],
            'order' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
