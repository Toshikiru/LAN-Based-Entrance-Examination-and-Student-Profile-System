<?php

namespace App\Http\Requests\Counselor;

use Illuminate\Foundation\Http\FormRequest;

class ImportQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCounselor() ?? false;
    }

    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:txt,docx,pdf', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'document' => 'file',
        ];
    }

    public function messages(): array
    {
        return [
            'document.mimes' => 'Please upload a .txt, .docx, or .pdf file.',
            'document.max' => 'The file may not be larger than 10 MB.',
        ];
    }
}
