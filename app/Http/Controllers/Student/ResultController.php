<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamSessionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    /**
     * All of this student's completed, scored examinations.
     */
    public function index(Request $request): View
    {
        $sessions = $request->user()
            ->examSessions()
            ->where('status', ExamSessionStatus::Completed)
            // A session's exam can be soft-deleted out from under it — the view
            // dereferences $session->exam unconditionally, so exclude orphans here.
            ->whereHas('exam')
            ->with(['exam', 'result'])
            ->latest('submitted_at')
            ->paginate(15);

        return view('student.results.index', ['sessions' => $sessions]);
    }
}
