<?php

namespace App\Http\Requests\Counselor;

use App\Enums\ReportType;
use App\Models\ReportSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCounselor() ?? false;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(array_column(ReportType::cases(), 'value'))],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'frequency' => ['required', Rule::in(array_keys(ReportSchedule::FREQUENCIES))],
        ];
    }

    public function attributes(): array
    {
        return [
            'report_type' => 'report',
            'recipient_name' => 'recipient name',
            'recipient_email' => 'recipient email',
        ];
    }
}
