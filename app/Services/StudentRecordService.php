<?php

namespace App\Services;

use App\Enums\ExamSessionStatus;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Aggregates a student's examination history and per-section assessment
 * results from real Exam/ExamSession/ExamAnswer data. Shared by the Student
 * Profile tabs and the printable Cumulative Record.
 */
class StudentRecordService
{
    /**
     * Every exam attempt for this student, most recent first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function examHistory(User $student): Collection
    {
        return $student->examSessions()
            ->with(['exam', 'result'])
            ->latest('started_at')
            ->get()
            ->map(function (ExamSession $session) {
                $result = $session->result;
                $percentage = $result ? (float) $result->percentage : null;

                return [
                    'session' => $session,
                    'exam' => $session->exam,
                    'title' => $session->exam?->title ?? 'Untitled Examination',
                    'date' => $session->submitted_at ?? $session->started_at,
                    'score' => $result ? (float) $result->total_score : null,
                    'max_score' => $result ? (float) $result->max_score : null,
                    'percentage' => $percentage,
                    'status' => $session->status,
                    'passed' => $result?->passed,
                    'interpretation' => ($result && $session->exam)
                        ? $session->exam->interpretationFor($percentage)
                        : null,
                ];
            });
    }

    /**
     * Summary stats + per-exam, per-section score breakdowns for completed exams.
     *
     * @return array{total_exams: int, average_percentage: float, highest: ?array, breakdowns: Collection}
     */
    public function assessmentSummary(User $student): array
    {
        $completed = $student->examSessions()
            ->where('status', ExamSessionStatus::Completed)
            ->with(['exam.sections', 'result'])
            ->whereHas('result')
            ->latest('submitted_at')
            ->get();

        $breakdowns = $completed->map(fn (ExamSession $session) => [
            'session' => $session,
            'exam' => $session->exam,
            'percentage' => (float) $session->result->percentage,
            'passed' => $session->result->passed,
            'sections' => $this->sectionBreakdown($session),
        ]);

        $highest = $breakdowns->sortByDesc('percentage')->first();

        return [
            'total_exams' => $completed->count(),
            'average_percentage' => $completed->isNotEmpty()
                ? round($breakdowns->avg('percentage'), 1)
                : 0.0,
            'highest' => $highest,
            'breakdowns' => $breakdowns,
        ];
    }

    /**
     * Score per exam section for a single completed session. Likert items
     * are included — they're auto-scored proportionally to their selected
     * option's value by ExamTakingController::finalize(), same as every
     * other type, so their awarded_marks is already populated the same way.
     *
     * @return Collection<int, array{label: string, score: float, max: float, percentage: int}>
     */
    public function sectionBreakdown(ExamSession $session): Collection
    {
        $examQuestions = $session->exam?->examQuestions()->with(['question', 'examSection'])->get() ?? collect();
        $answers = $session->answers()->get()->keyBy('question_id');

        return $examQuestions
            ->filter(fn ($eq) => $eq->question !== null)
            ->groupBy(fn ($eq) => $eq->examSection?->title ?? 'General')
            ->map(function ($group, $label) use ($answers) {
                $max = $group->sum(fn ($eq) => (float) ($eq->marks_override ?? $eq->question->marks));
                $score = $group->sum(function ($eq) use ($answers) {
                    $answer = $answers->get($eq->question_id);

                    return $answer && $answer->awarded_marks !== null ? (float) $answer->awarded_marks : 0.0;
                });

                return [
                    'label' => $label,
                    'score' => $score,
                    'max' => $max,
                    'percentage' => $max > 0 ? (int) round(($score / $max) * 100) : 0,
                ];
            })
            ->values();
    }
}
