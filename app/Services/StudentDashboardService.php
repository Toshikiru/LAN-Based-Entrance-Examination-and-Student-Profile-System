<?php

namespace App\Services;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Aggregates the data shown on the student dashboard: available/upcoming
 * examinations (targeted to the student's department/year level), recent
 * results, notifications, and overall exam progress.
 */
class StudentDashboardService
{
    /**
     * Targeted, published exams currently open for this student to join,
     * excluding ones they've already completed.
     *
     * @return Collection<int, Exam>
     */
    public function availableExams(User $student, int $limit = 5): Collection
    {
        $completedExamIds = $student->examSessions()
            ->where('status', ExamSessionStatus::Completed)
            ->pluck('exam_id');

        return Exam::query()
            ->where('status', ExamStatus::Published)
            ->targetedTo($student)
            ->whereNotIn('id', $completedExamIds)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->withCount('examQuestions')
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Targeted, published exams scheduled to open in the future.
     *
     * @return Collection<int, Exam>
     */
    public function upcomingExams(User $student, int $limit = 5): Collection
    {
        return Exam::query()
            ->where('status', ExamStatus::Published)
            ->targetedTo($student)
            ->whereNotNull('starts_at')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }

    /**
     * This student's most recently completed, scored exam sessions.
     *
     * @return Collection<int, \App\Models\ExamSession>
     */
    public function recentResults(User $student, int $limit = 5): Collection
    {
        return $student->examSessions()
            ->where('status', ExamSessionStatus::Completed)
            ->with(['exam', 'result'])
            ->latest('submitted_at')
            ->limit($limit)
            ->get();
    }

    /**
     * The student's most recently started, still-in-progress session (if any),
     * for a "Continue Last Exam" call to action.
     */
    public function continuableSession(User $student): ?ExamSession
    {
        return $student->examSessions()
            ->where('status', ExamSessionStatus::InProgress)
            ->with('exam')
            ->latest('started_at')
            ->first();
    }

    /**
     * The student's database notifications (empty until the app starts sending any).
     *
     * @return Collection<int, \Illuminate\Notifications\DatabaseNotification>
     */
    public function notifications(User $student, int $limit = 5): Collection
    {
        return $student->notifications()->limit($limit)->get();
    }

    /**
     * Overall exam progress: how many joined sessions are completed vs. in progress.
     *
     * @return array{completed: int, in_progress: int, total_joined: int, percentage: int}
     */
    public function progressSummary(User $student): array
    {
        $completed = $student->examSessions()->where('status', ExamSessionStatus::Completed)->count();
        $inProgress = $student->examSessions()->where('status', ExamSessionStatus::InProgress)->count();
        $totalJoined = $student->examSessions()->count();

        return [
            'completed' => $completed,
            'in_progress' => $inProgress,
            'total_joined' => $totalJoined,
            'percentage' => $totalJoined > 0 ? (int) round(($completed / $totalJoined) * 100) : 0,
        ];
    }
}
