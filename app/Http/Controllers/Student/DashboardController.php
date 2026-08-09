<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected StudentDashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $student = $request->user();
        $student->load(['studentProfile', 'department']);

        return view('student.dashboard', [
            'student' => $student,
            'continuableSession' => $this->dashboard->continuableSession($student),
            'availableExams' => $this->dashboard->availableExams($student),
            'upcomingExams' => $this->dashboard->upcomingExams($student),
            'recentResults' => $this->dashboard->recentResults($student),
            'notifications' => $this->dashboard->notifications($student),
            'progress' => $this->dashboard->progressSummary($student),
        ]);
    }
}
