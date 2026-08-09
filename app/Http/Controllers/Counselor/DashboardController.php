<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Services\CounselorDashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected CounselorDashboardService $dashboard) {}

    public function index(): View
    {
        return view('counselor.dashboard', [
            'stats' => $this->dashboard->stats(),
            'upcomingExams' => $this->dashboard->upcomingExams(),
            'liveSessions' => $this->dashboard->liveSessions(),
            'pendingGrading' => $this->dashboard->pendingGradingSessions(),
            'recentStudentActivity' => $this->dashboard->recentStudentActivity(),
            'recentActivity' => $this->dashboard->recentActivity(),
        ]);
    }
}
