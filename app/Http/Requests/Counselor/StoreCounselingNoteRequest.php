<?php

namespace App\Http\Requests\Counselor;

use App\Enums\CounselingNoteCategory;
use App\Enums\CounselingNoteStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCounselingNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isCounselor() || $user->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(array_column(CounselingNoteCategory::cases(), 'value'))],
            'note_date' => ['required', 'date'],
            'content' => ['required', 'string', 'min:5'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:note_date'],
            'follow_up_action' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_column(CounselingNoteStatus::cases(), 'value'))],
        ];
    }

    public function attributes(): array
    {
        return [
            'note_date' => 'date',
            'follow_up_date' => 'follow-up date',
            'follow_up_action' => 'follow-up action',
        ];
    }
}
