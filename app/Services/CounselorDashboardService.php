<?php

namespace App\Services;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Aggregates the data shown on the counselor dashboard: headline stats,
 * upcoming/live sessions, pending grading, recent student activity, and
 * recent audit activity.
 */
class CounselorDashboardService
{
    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return [
            'total_students' => User::query()->where('role', UserRole::Student)->count(),
            'active_exams' => Exam::query()
                ->whereIn('status', [ExamStatus::Published, ExamStatus::Ongoing])
                ->count(),
            'pending_grading' => $this->pendingGradingQuery()->count(),
            'completed_exams' => ExamSession::query()
                ->where('status', ExamSessionStatus::Completed)
                ->count(),
            // Matches liveSessions()'s whereHas('exam') guard below — otherwise this
            // count could include a session whose exam was deleted out from under
            // it, showing "N active sessions" in the header while the table (which
            // excludes those orphans to avoid dereferencing a null exam) shows fewer.
            'live_session_count' => ExamSession::query()
                ->where('status', ExamSessionStatus::InProgress)
                ->whereHas('exam')
                ->count(),
        ];
    }

    /**
     * Published exams scheduled to open soon, for the "Upcoming Examinations" widget.
     *
     * @return Collection<int, Exam>
     */
    public function upcomingExams(int $limit = 5): Collection
    {
        return Exam::query()
            ->where('status', ExamStatus::Published)
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }

    /**
     * A preview of the most recently active exam sessions, for the Live Examination Sessions widget.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function liveSessions(int $limit = 5): Collection
    {
        return ExamSession::query()
            ->whereIn('status', [ExamSessionStatus::InProgress, ExamSessionStatus::Flagged])
            // A session's exam can be soft-deleted out from under it while
            // the session is still in progress — exclude those here (rather
            // than filtering the null relation out after the fact) so the
            // limit still returns up to $limit real rows.
            ->whereHas('exam')
            ->with(['student', 'exam' => fn ($q) => $q->withCount('examQuestions')])
            ->withCount(['answers as answered_count' => function ($q) {
                $q->where(function ($q) {
                    $q->whereNotNull('selected_option_id')->orWhereNotNull('answer_text');
                });
            }])
            ->latest('started_at')
            ->limit($limit)
            ->get()
            // whereHas('exam') above excludes orphaned sessions at query time, but the
            // exam can still be soft-deleted between that check and this eager-load
            // fetch — guard against the resulting null here rather than crash.
            ->filter(fn (ExamSession $session) => $session->exam !== null)
            ->map(function (ExamSession $session) {
                $totalQuestions = max(1, $session->exam->exam_questions_count);

                return [
                    'exam' => $session->exam,
                    'name' => $session->student?->name ?? '—',
                    'school_id' => $session->student?->school_id ?? '—',
                    'status' => $session->status,
                    'answered' => $session->answered_count,
                    'total' => $totalQuestions,
                    'progress' => (int) round(($session->answered_count / $totalQuestions) * 100),
                ];
            })
            ->values();
    }

    /**
     * Completed sessions still awaiting manual grading of short-answer items,
     * for the "Pending Manual Grading" widget.
     *
     * @return Collection<int, ExamSession>
     */
    public function pendingGradingSessions(int $limit = 5): Collection
    {
        return $this->pendingGradingQuery()
            ->with(['student', 'exam'])
            ->withCount(['answers as ungraded_count' => fn ($q) => $q
                ->whereNull('awarded_marks')
                ->whereHas('question', fn ($q) => $q->where('type', QuestionType::ShortAnswer))])
            ->latest('submitted_at')
            ->limit($limit)
            ->get();
    }

    /**
     * A merged, most-recent-first feed of students starting or submitting exams,
     * for the "Recent Student Activity" widget.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function recentStudentActivity(int $limit = 6): Collection
    {
        $started = ExamSession::query()
            ->whereNotNull('started_at')
            ->with(['student', 'exam'])
            ->latest('started_at')
            ->limit($limit)
            ->get()
            ->map(fn (ExamSession $s) => [
                'type' => 'started',
                'at' => $s->started_at,
                'student' => $s->student,
                'exam' => $s->exam,
            ]);

        $submitted = ExamSession::query()
            ->whereNotNull('submitted_at')
            ->with(['student', 'exam'])
            ->latest('submitted_at')
            ->limit($limit)
            ->get()
            ->map(fn (ExamSession $s) => [
                'type' => 'submitted',
                'at' => $s->submitted_at,
                'student' => $s->student,
                'exam' => $s->exam,
            ]);

        return $started->concat($submitted)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    /**
     * Most recent audit log entries, for the "Recent Audit Activity" widget.
     *
     * @return Collection<int, AuditLog>
     */
    public function recentActivity(int $limit = 5): Collection
    {
        return AuditLog::query()
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Completed sessions with at least one ungraded short-answer response.
     */
    protected function pendingGradingQuery(): Builder
    {
        return ExamSession::query()
            ->where('status', ExamSessionStatus::Completed)
            // Same soft-delete-orphan guard as liveSessions() — the dashboard
            // view links out to the exam (route('counselor.exams.grading.session', ...))
            // without a null check.
            ->whereHas('exam')
            ->whereHas('answers', fn ($q) => $q
                ->whereNull('awarded_marks')
                ->whereHas('question', fn ($q) => $q->where('type', QuestionType::ShortAnswer)));
    }
}
