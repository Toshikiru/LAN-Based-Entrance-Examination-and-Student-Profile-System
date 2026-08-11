<?php

namespace Database\Seeders;

use App\Enums\ExamCategory;
use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Ensures the counselor/student dashboards have at least one real, persisted
 * "upcoming" exam and one real "live" (in-progress) session to show — actual
 * rows that link through correctly, not synthetic placeholder data. Idempotent:
 * safe to re-run, each half skips itself once the real thing already exists.
 */
class DashboardDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureUpcomingExam();
        $this->ensureLiveSession();
    }

    protected function ensureUpcomingExam(): void
    {
        $title = 'Scholarship Qualifying Examination';

        if (Exam::withTrashed()->where('title', $title)->exists()) {
            return;
        }

        $counselor = User::where('role', UserRole::Counselor)->first();
        if (! $counselor) {
            return;
        }

        $exam = Exam::create([
            'title' => $title,
            'description' => 'Qualifying exam for the merit-based scholarship program.',
            'category' => ExamCategory::Entrance,
            'duration_minutes' => 90,
            'passing_score' => 75,
            'negative_marking' => false,
            'status' => ExamStatus::Published,
            'created_by' => $counselor->id,
            'starts_at' => now()->addDays(3)->setTime(9, 0),
            'ends_at' => now()->addDays(3)->setTime(12, 0),
        ]);

        $questions = Question::inRandomOrder()->limit(10)->get();
        foreach ($questions as $index => $question) {
            $exam->examQuestions()->create([
                'question_id' => $question->id,
                'order' => $index + 1,
            ]);
        }
    }

    protected function ensureLiveSession(): void
    {
        // Must match CounselorDashboardService::liveSessions()'s own filter —
        // an active-status row whose exam was since soft-deleted doesn't count
        // as "already have a live session" from the dashboard's point of view.
        $hasVisibleLiveSession = ExamSession::whereIn('status', [ExamSessionStatus::InProgress, ExamSessionStatus::Flagged])
            ->whereHas('exam')
            ->exists();

        if ($hasVisibleLiveSession) {
            return;
        }

        // A currently-open, published exam with questions already attached —
        // reuse whatever real exam is live right now rather than the one just
        // created above (which starts in the future and can't have a session yet).
        $exam = Exam::where('status', ExamStatus::Published)
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->whereHas('examQuestions')
            ->first();

        $exam ??= $this->createOpenExam();

        if (! $exam || ! $exam->duration_minutes) {
            return;
        }

        // Must avoid the DB-level "one active session per student" lock and the
        // (exam_id, student_id) unique constraint — pick a student clear of both.
        $lockedStudentIds = ExamSession::whereIn('status', [ExamSessionStatus::InProgress, ExamSessionStatus::Flagged])
            ->pluck('student_id');
        $studentsAlreadyOnExam = ExamSession::where('exam_id', $exam->id)->pluck('student_id');

        $student = User::where('role', UserRole::Student)
            ->whereNotIn('id', $lockedStudentIds->merge($studentsAlreadyOnExam))
            ->first();

        if (! $student) {
            return;
        }

        $examQuestions = $exam->examQuestions()->with('question.options')->get();
        $startedAt = now()->subMinutes((int) ($exam->duration_minutes * 0.3));

        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => ExamSessionStatus::InProgress,
            'started_at' => $startedAt,
            'time_remaining_seconds' => (int) ($exam->duration_minutes * 60 * 0.7),
        ]);

        foreach ($examQuestions->take((int) ceil($examQuestions->count() / 2)) as $eq) {
            if (! $eq->question) {
                continue;
            }

            $option = $eq->question->options->first();
            $session->answers()->create([
                'question_id' => $eq->question_id,
                'selected_option_id' => $option?->id,
                'is_visited' => true,
                'answered_at' => $startedAt->copy()->addMinutes(5),
            ]);
        }
    }

    /**
     * Falls back to this when nothing published happens to be open right now
     * (e.g. every existing exam's window has already closed) — a real,
     * persisted, wide-open exam so the live session below has somewhere valid
     * to attach to.
     */
    protected function createOpenExam(): ?Exam
    {
        $title = 'General Aptitude Screening';

        $existing = Exam::withTrashed()->where('title', $title)->first();
        if ($existing) {
            return $existing->trashed() ? null : $existing;
        }

        $counselor = User::where('role', UserRole::Counselor)->first();
        if (! $counselor) {
            return null;
        }

        $exam = Exam::create([
            'title' => $title,
            'description' => 'Ongoing screening exam used to validate applicant readiness.',
            'category' => ExamCategory::Entrance,
            'duration_minutes' => 60,
            'passing_score' => 70,
            'negative_marking' => false,
            'status' => ExamStatus::Published,
            'created_by' => $counselor->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(30),
        ]);

        $questions = Question::inRandomOrder()->limit(10)->get();
        foreach ($questions as $index => $question) {
            $exam->examQuestions()->create([
                'question_id' => $question->id,
                'order' => $index + 1,
            ]);
        }

        return $exam;
    }
}
