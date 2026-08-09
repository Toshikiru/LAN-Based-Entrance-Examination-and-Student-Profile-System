<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RestoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'backup_file' => ['required', 'file', 'extensions:sql', 'max:51200'],
            'confirm' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'backup_file' => 'backup file',
        ];
    }

    public function messages(): array
    {
        return [
            'confirm.accepted' => 'You must confirm that you understand this will overwrite all current data.',
        ];
    }
}
