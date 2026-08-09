<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentRecordService;
use Illuminate\View\View;

class StudentRecordController extends Controller
{
    public function __construct(protected StudentRecordService $records) {}

    /**
     * Print-optimized Cumulative Record (use the browser's Print → Save as PDF).
     *
     * Guidance & Counseling Notes are intentionally excluded from this document —
     * it's designed to be printable/exportable, and those notes must never leave
     * the confidential, access-controlled counseling module.
     */
    public function print(User $student): View
    {
        abort_unless($student->role === UserRole::Student, 404);

        $student->load(['studentProfile', 'department']);

        return view('counselor.students.record-print', [
            'student' => $student,
            'examHistory' => $this->records->examHistory($student),
            'assessment' => $this->records->assessmentSummary($student),
        ]);
    }
}
