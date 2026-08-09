<?php

namespace App\Http\Requests\Counselor;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCounselor() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'school_id' => ['required', 'string', 'max:50', 'unique:users,school_id'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'year_level' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:50'],
            'enrollment_status' => ['nullable', 'string', 'in:enrolled,on_leave,graduated,dropped'],
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'School ID',
            'department_id' => 'department',
            'year_level' => 'year level',
            'enrollment_status' => 'enrollment status',
        ];
    }
}
