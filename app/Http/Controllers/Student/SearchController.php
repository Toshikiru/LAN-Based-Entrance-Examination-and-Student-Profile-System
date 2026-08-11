<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Header search — matching exams (available or upcoming, targeted to
     * this student) and this student's own completed results.
     */
    public function index(Request $request): View
    {
        $student = $request->user();
        $query = trim((string) $request->query('search'));

        $exams = collect();
        $sessions = collect();

        if ($query !== '') {
            $exams = Exam::query()
                ->where('status', ExamStatus::Published)
                ->targetedTo($student)
                ->where('title', 'like', "%{$query}%")
                ->orderBy('starts_at')
                ->limit(5)
                ->get();

            $sessions = $student->examSessions()
                ->where('status', ExamSessionStatus::Completed)
                ->whereHas('exam', fn ($q) => $q->where('title', 'like', "%{$query}%"))
                ->with(['exam', 'result'])
                ->latest('submitted_at')
                ->limit(5)
                ->get();
        }

        return view('student.search', [
            'query' => $query,
            'exams' => $exams,
            'sessions' => $sessions,
        ]);
    }
}
