<?php

namespace Database\Seeders;

use App\Enums\ExamSessionStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Realistic exam attempts (completed/in-progress/flagged) for the seeded
 * students against the seeded exam, so dashboards, reports, live monitoring,
 * and the grading queue all have real data to display out of the box.
 *
 * Guarded so re-running `db:seed` never creates duplicate sessions.
 */
class ExamSessionSeeder extends Seeder
{
    public function run(): void
    {
        if (ExamSession::count() > 0) {
            return;
        }

        $exam = Exam::first();
        if (! $exam) {
            return;
        }

        $students = User::where('role', UserRole::Student)->orderBy('id')->get();
        if ($students->isEmpty()) {
            return;
        }

        $examQuestions = $exam->examQuestions()->with('question.options')->get();
        $totalMinutes = $exam->duration_minutes;

        // ---- Completed sessions: a mix of passing and failing scores ----
        $completedPlan = [
            ['index' => 0, 'daysAgo' => 5, 'correctRatio' => 0.9],  // strong pass
            ['index' => 1, 'daysAgo' => 4, 'correctRatio' => 0.7],  // pass
            ['index' => 2, 'daysAgo' => 3, 'correctRatio' => 0.4],  // fail
            ['index' => 3, 'daysAgo' => 2, 'correctRatio' => 0.65], // pass, short-answer left ungraded
            ['index' => 4, 'daysAgo' => 1, 'correctRatio' => 0.5],  // borderline
        ];

        foreach ($completedPlan as $i => $plan) {
            $student = $students->get($plan['index']);
            if (! $student) {
                continue;
            }

            $startedAt = now()->subDays($plan['daysAgo'])->subMinutes($totalMinutes);
            $submittedAt = now()->subDays($plan['daysAgo']);

            $session = ExamSession::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'status' => ExamSessionStatus::Completed,
                'started_at' => $startedAt,
                'submitted_at' => $submittedAt,
                'auto_submitted' => false,
                'time_remaining_seconds' => 0,
            ]);

            $totalScore = 0.0;
            $maxScore = 0.0;
            $leaveShortAnswerUngraded = $i === 3;

            foreach ($examQuestions as $index => $eq) {
                $question = $eq->question;
                if (! $question) {
                    continue;
                }
                $points = (float) ($eq->marks_override ?? $question->marks);

                if ($question->type === QuestionType::Likert) {
                    $option = $question->options->random();
                    $session->answers()->create([
                        'question_id' => $question->id,
                        'selected_option_id' => $option->id,
                        'is_visited' => true,
                        'answered_at' => $startedAt->copy()->addMinutes($index * 2),
                    ]);

                    continue;
                }

                if ($question->type === QuestionType::ShortAnswer) {
                    $maxScore += $points;
                    $answer = $session->answers()->create([
                        'question_id' => $question->id,
                        'answer_text' => 'I resolved the disagreement by listening to each side and proposing a compromise both members accepted.',
                        'is_visited' => true,
                        'answered_at' => $startedAt->copy()->addMinutes($index * 2),
                    ]);

                    if (! $leaveShortAnswerUngraded) {
                        $awarded = round($points * $plan['correctRatio'], 2);
                        $answer->update(['awarded_marks' => $awarded, 'is_correct' => $awarded > 0]);
                        $totalScore += $awarded;
                    }

                    continue;
                }

                // Multiple choice / true-false — auto-scored.
                $maxScore += $points;
                $shouldAnswerCorrectly = (($index + 1) / max(1, $examQuestions->count())) <= $plan['correctRatio'];
                $option = $shouldAnswerCorrectly
                    ? $question->options->firstWhere('is_correct', true)
                    : $question->options->firstWhere('is_correct', false);
                $option ??= $question->options->first();
                $correct = (bool) $option?->is_correct;
                $awarded = $correct ? $points : -1 * (float) $question->negative_marks;

                $session->answers()->create([
                    'question_id' => $question->id,
                    'selected_option_id' => $option?->id,
                    'is_correct' => $correct,
                    'awarded_marks' => $awarded,
                    'is_visited' => true,
                    'answered_at' => $startedAt->copy()->addMinutes($index * 2),
                ]);

                $totalScore += $awarded;
            }

            $totalScore = max(0, $totalScore);
            $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;

            ExamResult::create([
                'exam_session_id' => $session->id,
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'passed' => $percentage >= (float) $exam->passing_score,
                'computed_at' => $submittedAt,
            ]);
        }

        // ---- One in-progress session (for Live Monitoring demo) ----
        if ($student = $students->get(5)) {
            $startedAt = now()->subMinutes((int) ($totalMinutes * 0.3));
            $session = ExamSession::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'status' => ExamSessionStatus::InProgress,
                'started_at' => $startedAt,
                'time_remaining_seconds' => (int) ($totalMinutes * 60 * 0.7),
            ]);

            foreach ($examQuestions->take(2) as $eq) {
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

        // ---- One flagged session (for Live Monitoring demo) ----
        if ($student = $students->get(6)) {
            $startedAt = now()->subMinutes((int) ($totalMinutes * 0.5));
            ExamSession::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'status' => ExamSessionStatus::Flagged,
                'started_at' => $startedAt,
                'time_remaining_seconds' => (int) ($totalMinutes * 60 * 0.5),
            ]);
        }
    }
}
