<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:courses,code'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'duration_years' => ['nullable', 'integer', 'min:0', 'max:10'],
            'duration_semesters' => ['nullable', 'integer', 'min:0', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'course code',
            'department_id' => 'department',
            'duration_years' => 'duration (years)',
            'duration_semesters' => 'duration (semesters)',
        ];
    }
}
