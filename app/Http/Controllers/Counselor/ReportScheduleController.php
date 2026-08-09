<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\StoreReportScheduleRequest;
use App\Models\ReportSchedule;
use App\Services\ReportScheduleService;
use Illuminate\Http\RedirectResponse;

class ReportScheduleController extends Controller
{
    public function __construct(protected ReportScheduleService $schedules) {}

    public function store(StoreReportScheduleRequest $request): RedirectResponse
    {
        $this->schedules->create($request->validated(), $request->user());

        return redirect()
            ->route('counselor.reports.index')
            ->with('status', 'Report schedule saved.');
    }

    public function toggle(ReportSchedule $schedule): RedirectResponse
    {
        $this->schedules->toggle($schedule);

        return redirect()
            ->route('counselor.reports.index')
            ->with('status', $schedule->is_active ? "Schedule for {$schedule->recipient_name} enabled." : "Schedule for {$schedule->recipient_name} paused.");
    }
}
