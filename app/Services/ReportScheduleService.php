<?php

namespace App\Services;

use App\Models\ReportSchedule;
use App\Models\User;

class ReportScheduleService
{
    /**
     * @param  array{report_type: string, recipient_name: string, recipient_email: string, frequency: string}  $data
     */
    public function create(array $data, User $creator): ReportSchedule
    {
        return ReportSchedule::create([
            ...$data,
            'is_active' => true,
            'created_by' => $creator->id,
        ]);
    }

    public function toggle(ReportSchedule $schedule): ReportSchedule
    {
        $schedule->update(['is_active' => ! $schedule->is_active]);

        return $schedule->fresh();
    }
}
