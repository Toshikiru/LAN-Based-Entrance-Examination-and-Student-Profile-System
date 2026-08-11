<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamResult;
use App\Models\ExamSession;
use App\Notifications\ExaminationSubmitted;
use App\Notifications\ManualGradingRequired;
use App\Notifications\ResultsReleased;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamTakingController extends Controller
{
    /**
     * Join screen — enter an access code.
     */
    public function join(): View
    {
        $sessions = ExamSession::where('student_id', auth()->id())
            // A session's exam can be soft-deleted out from under it — the view
            // dereferences $session->exam unconditionally, so exclude orphans here.
            ->whereHas('exam')
            ->with('exam')
            ->latest()
            ->get();

        return view('student.exams.join', ['sessions' => $sessions]);
    }

    /**
     * Validate the access code and start (or resume) a session.
     */
    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
        ]);

        $exam = Exam::where('access_code', strtoupper(trim($data['access_code'])))
            ->where('status', ExamStatus::Published)
            ->first();

        if (! $exam) {
            return back()->withInput()->with('error', 'No published exam found for that access code.');
        }

        $now = now();
        if ($exam->starts_at && $now->lt($exam->starts_at)) {
            return back()->with('error', 'This exam has not opened yet.');
        }
        if ($exam->ends_at && $now->gt($exam->ends_at)) {
            return back()->with('error', 'This exam is already closed.');
        }
        if ($exam->examQuestions()->count() === 0) {
            return back()->with('error', 'This exam has no questions yet.');
        }

        // One active session per student, across all exams — resuming this
        // same exam's own in-progress session further down is unaffected
        // (it's excluded here by exam_id), only *starting a different* exam
        // while another is still active is blocked.
        $otherActiveSession = ExamSession::where('student_id', auth()->id())
            ->where('exam_id', '!=', $exam->id)
            ->whereIn('status', [ExamSessionStatus::InProgress, ExamSessionStatus::Flagged])
            ->with('exam')
            ->first();

        if ($otherActiveSession) {
            return back()->with('error', "You already have an active exam session for \"{$otherActiveSession->exam?->title}\". Finish or submit that exam before starting another.");
        }

        $session = ExamSession::firstOrCreate(
            ['exam_id' => $exam->id, 'student_id' => auth()->id()],
            ['status' => ExamSessionStatus::NotStarted]
        );

        if ($session->status === ExamSessionStatus::Completed) {
            return redirect()->route('student.exams.result', $exam);
        }

        if ($session->status === ExamSessionStatus::NotStarted) {
            try {
                $session->update([
                    'status' => ExamSessionStatus::InProgress,
                    'started_at' => $now,
                    'time_remaining_seconds' => $exam->duration_minutes ? $exam->duration_minutes * 60 : null,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Backstop for the `exam_sessions_one_active_per_student`
                // unique index — only reachable if two requests race past the
                // check above at almost the same instant (e.g. two tabs).
                if (str_contains($e->getMessage(), 'exam_sessions_one_active_per_student')) {
                    return back()->with('error', 'You already have an active exam session. Finish or submit that exam before starting another.');
                }

                throw $e;
            }

            // Pre-create answer rows for the navigator.
            foreach ($exam->examQuestions()->pluck('question_id') as $questionId) {
                ExamAnswer::firstOrCreate([
                    'exam_session_id' => $session->id,
                    'question_id' => $questionId,
                ]);
            }
        }

        return redirect()->route('student.exams.take', $exam);
    }

    /**
     * The exam runner.
     */
    public function take(Request $request, Exam $exam): View|RedirectResponse
    {
        $session = $this->sessionFor($exam);

        if (! $session || $session->status === ExamSessionStatus::NotStarted) {
            return redirect()->route('student.exams.join')->with('error', 'Enter the access code to start this exam.');
        }
        if ($session->status === ExamSessionStatus::Completed) {
            return redirect()->route('student.exams.result', $exam);
        }

        // Time check — auto-submit if the window has elapsed. No duration set
        // means this exam has no time limit, so skip the check entirely.
        $remaining = $this->remainingSeconds($session, $exam);
        if ($remaining !== null && $remaining <= 0) {
            $this->finalize($session, $exam, autoSubmitted: true);

            return redirect()->route('student.exams.result', $exam);
        }

        $questions = $this->orderedQuestions($session, $exam);
        $answers = $session->answers()->get()->keyBy('question_id');

        $index = (int) $request->query('q', 0);
        $index = max(0, min($index, $questions->count() - 1));
        $current = $questions[$index];

        $options = $current->options;
        if ($exam->shuffle_choices && $current->type !== QuestionType::ShortAnswer) {
            $options = $options->shuffle($session->id + $current->id);
        }

        return view('student.exams.runner', [
            'exam' => $exam,
            'session' => $session,
            'questions' => $questions,
            'answers' => $answers,
            'current' => $current,
            'options' => $options,
            'index' => $index,
            'remaining' => $remaining,
        ]);
    }

    /**
     * Save the current answer and navigate.
     */
    public function answer(Request $request, Exam $exam): RedirectResponse
    {
        $session = $this->sessionFor($exam);
        if (! $session || $session->status !== ExamSessionStatus::InProgress) {
            return redirect()->route('student.exams.join');
        }

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'nav' => ['required', 'string'],
            'current_index' => ['required', 'integer'],
            'selected_option_id' => ['nullable', 'integer'],
            'answer_text' => ['nullable', 'string', 'max:5000'],
        ]);

        $nav = $data['nav'];

        // Only accept a question that actually belongs to this exam.
        $belongs = $exam->examQuestions()->where('question_id', $data['question_id'])->exists();

        if ($belongs) {
            $answer = ExamAnswer::firstOrNew([
                'exam_session_id' => $session->id,
                'question_id' => $data['question_id'],
            ]);

            if ($nav === 'flag') {
                $answer->is_flagged = ! $answer->is_flagged;
            }

            // Validate the option is one of this question's options.
            $optionId = $data['selected_option_id'] ?? null;
            if ($optionId) {
                $valid = \App\Models\QuestionOption::where('id', $optionId)
                    ->where('question_id', $data['question_id'])->exists();
                $optionId = $valid ? $optionId : null;
            }

            $answer->selected_option_id = $optionId;
            $answer->answer_text = $data['answer_text'] ?? null;
            $answer->is_visited = true;
            if ($optionId || ! empty($data['answer_text'])) {
                $answer->answered_at = now();
            }
            $answer->save();
        }

        if ($nav === 'submit') {
            if ($session->status !== ExamSessionStatus::Completed) {
                $this->finalize($session, $exam, autoSubmitted: false);
            }

            return redirect()->route('student.exams.result', $exam);
        }

        $target = $data['current_index'];
        if ($nav === 'next') {
            $target++;
        } elseif ($nav === 'prev') {
            $target--;
        } elseif (str_starts_with($nav, 'goto:')) {
            $target = (int) substr($nav, 5);
        }

        return redirect()->route('student.exams.take', ['exam' => $exam, 'q' => $target]);
    }

    /**
     * Finalize and score the exam.
     */
    public function submit(Exam $exam): RedirectResponse
    {
        $session = $this->sessionFor($exam);
        if (! $session) {
            return redirect()->route('student.exams.join');
        }
        if ($session->status === ExamSessionStatus::Completed) {
            return redirect()->route('student.exams.result', $exam);
        }

        $this->finalize($session, $exam, autoSubmitted: false);

        return redirect()->route('student.exams.result', $exam);
    }

    /**
     * Submission summary.
     */
    public function result(Exam $exam): View|RedirectResponse
    {
        $session = $this->sessionFor($exam);
        if (! $session || $session->status !== ExamSessionStatus::Completed) {
            return redirect()->route('student.exams.join');
        }

        $result = $session->result;
        $pendingManual = $session->answers()
            ->whereHas('question', fn ($q) => $q->where('type', QuestionType::ShortAnswer))
            ->whereNull('awarded_marks')
            ->exists();

        return view('student.exams.result', [
            'exam' => $exam,
            'session' => $session,
            'result' => $result,
            'pendingManual' => $pendingManual,
        ]);
    }

    // ---- Helpers ----

    protected function sessionFor(Exam $exam): ?ExamSession
    {
        return ExamSession::where('exam_id', $exam->id)
            ->where('student_id', auth()->id())
            ->first();
    }

    protected function orderedQuestions(ExamSession $session, Exam $exam)
    {
        $questions = $exam->questions()->with('options')->get();

        if ($exam->shuffle_questions) {
            $questions = $questions->shuffle($session->id)->values();
        }

        return $questions->values();
    }

    protected function remainingSeconds(ExamSession $session, Exam $exam): ?int
    {
        // No duration configured on the exam means no time limit at all.
        if ($exam->duration_minutes === null) {
            return null;
        }

        if (! $session->started_at) {
            return $exam->duration_minutes * 60;
        }

        // Use the duration frozen on the session at start time, not the exam's
        // current duration_minutes — otherwise editing an exam's duration while
        // a student is mid-session changes their countdown out from under them.
        $totalSeconds = $session->time_remaining_seconds ?? ($exam->duration_minutes * 60);
        // diffInSeconds() is signed (started_at - now), not absolute — pass the
        // instant to diff against explicitly with absolute:true, or a session
        // that's been running a while ends up with a *negative* elapsed value,
        // which makes remaining time grow instead of count down.
        $elapsed = $session->started_at->diffInSeconds(now(), absolute: true);

        return max(0, $totalSeconds - $elapsed);
    }

    /**
     * Score objective items, store the result, and close the session.
     * Likert items are auto-scored proportionally to the selected option's
     * `value` against the highest `value` among that question's own options
     * (there is no single "correct" Likert option — see QuestionOption's
     * `value` column and QuestionController::syncOptions()). Short Answer
     * items are left pending for manual grading.
     */
    protected function finalize(ExamSession $session, Exam $exam, bool $autoSubmitted): void
    {
        $shortAnswerMax = 0.0;

        DB::transaction(function () use ($session, $exam, $autoSubmitted, &$shortAnswerMax) {
            $examQuestions = $exam->examQuestions()->with('question.options')->get();
            $answers = $session->answers()->get()->keyBy('question_id');

            $totalScore = 0.0;
            $objectiveMax = 0.0;

            foreach ($examQuestions as $eq) {
                $question = $eq->question;
                if (! $question) {
                    continue;
                }
                $points = (float) ($eq->marks_override ?? $question->marks);
                $answer = $answers->get($question->id);

                if ($question->type === QuestionType::ShortAnswer) {
                    $shortAnswerMax += $points;
                    continue; // pending manual grading
                }

                if ($question->type === QuestionType::Likert) {
                    // Likert options have no `is_correct` (they're attitudinal,
                    // not right/wrong) — only a numeric `value` per option, set
                    // by the counselor and validated in StoreQuestionRequest.
                    // Score proportionally to how high on that question's own
                    // scale the selected option sits, so it counts as an
                    // auto-scored objective item without inventing a new
                    // "correct answer" concept that doesn't exist anywhere
                    // else in the authoring UI.
                    $objectiveMax += $points;

                    if ($answer && $answer->selected_option_id) {
                        $selected = $question->options->firstWhere('id', $answer->selected_option_id);
                        $maxValue = (int) $question->options->max('value');

                        if ($selected && $selected->value !== null && $maxValue > 0) {
                            $awarded = round(($selected->value / $maxValue) * $points, 2);

                            $answer->update(['awarded_marks' => $awarded]);

                            $totalScore += $awarded;
                        }
                    }

                    continue;
                }

                // Multiple Choice / True-False — auto-score.
                $objectiveMax += $points;

                if ($answer && $answer->selected_option_id) {
                    $selected = $question->options->firstWhere('id', $answer->selected_option_id);
                    $correct = (bool) ($selected?->is_correct);
                    $awarded = $correct ? $points : -1 * (float) $question->negative_marks;

                    $answer->update([
                        'is_correct' => $correct,
                        'awarded_marks' => $awarded,
                    ]);

                    $totalScore += $awarded;
                }
            }

            $totalScore = max(0, $totalScore);
            $maxScore = $objectiveMax + $shortAnswerMax;
            $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;

            ExamResult::updateOrCreate(
                ['exam_session_id' => $session->id],
                [
                    'total_score' => $totalScore,
                    'max_score' => $maxScore,
                    'percentage' => $percentage,
                    'passed' => $percentage >= (float) $exam->passing_score,
                    'computed_at' => now(),
                ]
            );

            $session->update([
                'status' => ExamSessionStatus::Completed,
                'submitted_at' => now(),
                'auto_submitted' => $autoSubmitted,
                'time_remaining_seconds' => 0,
            ]);
        });

        $exam->creator?->notify(new ExaminationSubmitted($session));

        if ($shortAnswerMax > 0) {
            $exam->creator?->notify(new ManualGradingRequired($session));
        } elseif ($exam->show_score) {
            $session->student?->notify(new ResultsReleased($exam));
        }
    }
}
