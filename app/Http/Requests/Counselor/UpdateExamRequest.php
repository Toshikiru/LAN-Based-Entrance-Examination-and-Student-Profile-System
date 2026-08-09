<?php

namespace App\Http\Requests\Counselor;

use App\Http\Controllers\Counselor\ExamController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCounselor() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'year_level' => ['nullable', Rule::in(array_keys(ExamController::YEAR_LEVELS))],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $starts = $this->input('starts_at');
            $ends = $this->input('ends_at');

            if ($starts && $ends && strtotime($ends) <= strtotime($starts)) {
                $validator->errors()->add('ends_at', 'The end time must be after the start time.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'department_id' => 'course',
            'year_level' => 'year level',
            'duration_minutes' => 'duration',
            'starts_at' => 'start date & time',
            'ends_at' => 'end date & time',
        ];
    }
}
