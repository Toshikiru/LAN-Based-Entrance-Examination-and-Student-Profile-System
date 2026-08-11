<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\ExamSessionStatus;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Notifications\ResultsReleased;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamResultController extends Controller
{
    /**
     * Landing page: pick an exam to view results for.
     */
    public function index(Request $request): View
    {
        $query = Exam::query()
            ->withCount(['examSessions as submissions_count' => fn ($q) => $q->where('status', ExamSessionStatus::Completed)]);

        if ($search = trim((string) $request->query('search'))) {
            $query->where('title', 'like', "%{$search}%");
        }

        $exams = $query->latest()->paginate(15)->withQueryString();

        return view('counselor.results.index', [
            'exams' => $exams,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Results management — completed sessions with scores, search + passers filter.
     */
    public function results(Request $request, Exam $exam): View
    {
        $exam->load('interpretationRanges');
        $sessions = $this->completedSessions($exam, $request);

        $submitted = $exam->examSessions()->where('exam_sessions.status', ExamSessionStatus::Completed)->count();
        $passers = $exam->examSessions()
            ->where('exam_sessions.status', ExamSessionStatus::Completed)
            ->whereHas('result', fn ($q) => $q->where('passed', true))->count();

        $stats = [
            'submitted' => $submitted,
            'passers' => $passers,
            'passing_rate' => $submitted > 0 ? round(($passers / $submitted) * 100, 1) : 0,
        ];

        return view('counselor.exams.results', [
            'exam' => $exam,
            'sessions' => $sessions,
            'stats' => $stats,
            'distribution' => $this->distribution($exam),
            'filters' => $request->only(['search', 'result']),
            'pendingGrading' => $this->pendingGradingCount($exam),
        ]);
    }

    /**
     * Percentage buckets (0-59, 60-69, ... 90-100) for the distribution chart.
     *
     * @return array{labels: array<int, string>, counts: array<int, int>}
     */
    protected function distribution(Exam $exam): array
    {
        $bands = [
            'Below 60' => fn ($p) => $p < 60,
            '60–69' => fn ($p) => $p >= 60 && $p < 70,
            '70–79' => fn ($p) => $p >= 70 && $p < 80,
            '80–89' => fn ($p) => $p >= 80 && $p < 90,
            '90–100' => fn ($p) => $p >= 90,
        ];

        $percentages = $exam->examSessions()
            ->where('exam_sessions.status', ExamSessionStatus::Completed)
            ->with('result')
            ->get()
            ->map(fn ($s) => (float) ($s->result?->percentage ?? 0));

        $counts = [];
        foreach ($bands as $test) {
            $counts[] = $percentages->filter($test)->count();
        }

        return ['labels' => array_keys($bands), 'counts' => $counts];
    }

    public function exportCsv(Request $request, Exam $exam): StreamedResponse
    {
        $sessions = $this->completedSessions($exam, $request, paginate: false);
        $filename = 'results-' . str($exam->title)->slug() . '.csv';

        return response()->streamDownload(function () use ($sessions, $exam) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student', 'School ID', 'Score', 'Max', 'Percentage', 'Result', 'Interpretation', 'Submitted At']);

            foreach ($sessions as $session) {
                $r = $session->result;
                fputcsv($out, [
                    $session->student?->name,
                    $session->student?->school_id,
                    $r?->total_score ?? '',
                    $r?->max_score ?? '',
                    $r ? $r->percentage . '%' : '',
                    $r?->passed ? 'Passed' : 'Failed',
                    $r ? ($exam->interpretationFor((float) $r->percentage) ?? '') : '',
                    $session->submitted_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Print-optimized results page (use the browser's Print → Save as PDF).
     */
    public function exportPrint(Request $request, Exam $exam): View
    {
        return view('counselor.exams.results-print', [
            'exam' => $exam,
            'sessions' => $this->completedSessions($exam, $request, paginate: false),
        ]);
    }

    /**
     * Sessions that still have ungraded short-answer responses.
     */
    public function grading(Exam $exam): View
    {
        $sessions = $exam->examSessions()
            ->where('status', ExamSessionStatus::Completed)
            ->with('student')
            ->whereHas('answers', fn ($q) => $q
                ->whereNull('awarded_marks')
                ->whereHas('question', fn ($q) => $q->where('type', QuestionType::ShortAnswer)))
            ->get()
            ->sortBy(fn ($s) => $s->student?->name)
            ->values();

        return view('counselor.exams.grading', [
            'exam' => $exam,
            'sessions' => $sessions,
        ]);
    }

    public function gradeSession(Exam $exam, ExamSession $session): View
    {
        abort_unless($session->exam_id === $exam->id, 404);

        $answers = $session->answers()
            ->whereHas('question', fn ($q) => $q->where('type', QuestionType::ShortAnswer))
            ->with('question')
            ->get();

        // Map each short-answer question to its points for this exam.
        $points = $exam->examQuestions()
            ->with('question')
            ->get()
            ->mapWithKeys(fn ($eq) => [$eq->question_id => (float) ($eq->marks_override ?? $eq->question?->marks ?? 0)]);

        return view('counselor.exams.grade-session', [
            'exam' => $exam,
            'session' => $session,
            'answers' => $answers,
            'points' => $points,
        ]);
    }

    public function storeGrades(Request $request, Exam $exam, ExamSession $session): RedirectResponse
    {
        abort_unless($session->exam_id === $exam->id, 404);

        $data = $request->validate([
            'marks' => ['required', 'array'],
            'marks.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $exam, $session) {
            foreach ($data['marks'] as $answerId => $mark) {
                $answer = $session->answers()->whereKey($answerId)->first();
                if (! $answer) {
                    continue;
                }
                $answer->update([
                    'awarded_marks' => $mark === null ? 0 : (float) $mark,
                    'is_correct' => $mark !== null && (float) $mark > 0,
                ]);
            }

            $this->recomputeResult($exam, $session);
        });

        $this->logAudit('exam.graded', $exam, "Graded short-answer items for {$session->student?->name}.");

        if ($exam->show_score) {
            $session->student?->notify(new ResultsReleased($exam));
        }

        return redirect()
            ->route('counselor.exams.grading', $exam)
            ->with('status', "Grading saved for {$session->student?->name}.");
    }

    // ---- Helpers ----

    protected function completedSessions(Exam $exam, Request $request, bool $paginate = true)
    {
        $query = $exam->examSessions()
            ->where('exam_sessions.status', ExamSessionStatus::Completed)
            ->with(['student', 'result'])
            ->join('users', 'users.id', '=', 'exam_sessions.student_id')
            ->select('exam_sessions.*');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.school_id', 'like', "%{$search}%");
            });
        }

        $result = $request->query('result');
        if ($result === 'passers' || $result === 'non-passers') {
            $passed = $result === 'passers';
            $query->whereHas('result', fn ($q) => $q->where('passed', $passed));
        }

        $query->orderByDesc('exam_sessions.submitted_at');

        return $paginate
            ? $query->paginate(15)->withQueryString()
            : $query->get();
    }

    protected function pendingGradingCount(Exam $exam): int
    {
        return $exam->examSessions()
            ->where('status', ExamSessionStatus::Completed)
            ->whereHas('answers', fn ($q) => $q
                ->whereNull('awarded_marks')
                ->whereHas('question', fn ($q) => $q->where('type', QuestionType::ShortAnswer)))
            ->count();
    }

    /**
     * Recompute a session's result from all awarded marks (objective + graded short answers).
     */
    protected function recomputeResult(Exam $exam, ExamSession $session): void
    {
        $examQuestions = $exam->examQuestions()->with('question')->get();
        $answers = $session->answers()->get()->keyBy('question_id');

        $totalScore = 0.0;
        $maxScore = 0.0;

        foreach ($examQuestions as $eq) {
            $question = $eq->question;
            if (! $question) {
                continue;
            }
            // Likert items are auto-scored proportionally by
            // ExamTakingController::finalize() (there's no "correct" Likert
            // answer, only a value-weighted award) — their awarded_marks is
            // already correctly populated there, same as every other type,
            // so no special-casing is needed here.
            $points = (float) ($eq->marks_override ?? $question->marks);
            $maxScore += $points;

            $answer = $answers->get($question->id);
            if ($answer && $answer->awarded_marks !== null) {
                $totalScore += (float) $answer->awarded_marks;
            }
        }

        $totalScore = max(0, $totalScore);
        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;

        $session->result()->updateOrCreate(
            ['exam_session_id' => $session->id],
            [
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'passed' => $percentage >= (float) $exam->passing_score,
                'computed_at' => now(),
            ]
        );
    }

    protected function logAudit(string $action, Exam $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => Exam::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
