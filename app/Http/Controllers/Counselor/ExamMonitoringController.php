<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\ExamSessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamMonitoringController extends Controller
{
    /**
     * Landing page: pick an exam to monitor.
     */
    public function index(Request $request): View
    {
        $query = Exam::query()
            ->withCount(['examSessions as in_progress_count' => fn ($q) => $q->where('status', ExamSessionStatus::InProgress)]);

        if ($search = trim((string) $request->query('search'))) {
            $query->where('title', 'like', "%{$search}%");
        }

        $exams = $query->latest()->paginate(15)->withQueryString();

        return view('counselor.monitoring.index', [
            'exams' => $exams,
            'filters' => $request->only(['search']),
        ]);
    }

    public function monitor(Request $request, Exam $exam): View
    {
        return view('counselor.exams.monitor', [
            'exam' => $exam,
            'rows' => $this->rows($exam, $request),
            'totalQuestions' => $exam->examQuestions()->count(),
            'stats' => $this->stats($exam),
            'search' => $request->query('search'),
        ]);
    }

    /**
     * Polled every few seconds — returns just the table body partial.
     */
    public function monitorData(Request $request, Exam $exam): View
    {
        return view('counselor.exams.partials.monitor-rows', [
            'exam' => $exam,
            'rows' => $this->rows($exam, $request),
            'totalQuestions' => $exam->examQuestions()->count(),
        ]);
    }

    public function flag(Exam $exam, ExamSession $session): RedirectResponse
    {
        abort_unless($session->exam_id === $exam->id, 404);

        $session->update([
            'status' => $session->status === ExamSessionStatus::Flagged
                ? ExamSessionStatus::InProgress
                : ExamSessionStatus::Flagged,
        ]);

        return back()->with('status', 'Session updated.');
    }

    public function terminate(Exam $exam, ExamSession $session): RedirectResponse
    {
        abort_unless($session->exam_id === $exam->id, 404);

        $session->update([
            'status' => ExamSessionStatus::Terminated,
            'submitted_at' => $session->submitted_at ?? now(),
        ]);

        return back()->with('status', 'Session terminated.');
    }

    /**
     * Summary counts for the header cards.
     */
    protected function stats(Exam $exam): array
    {
        $base = fn () => $exam->examSessions();

        return [
            'joined' => $base()->count(),
            'in_progress' => $base()->where('status', ExamSessionStatus::InProgress)->count(),
            'submitted' => $base()->where('status', ExamSessionStatus::Completed)->count(),
            'flagged' => $base()->whereIn('status', [ExamSessionStatus::Flagged, ExamSessionStatus::Terminated])->count(),
        ];
    }

    /**
     * Build per-student monitoring rows.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function rows(Exam $exam, Request $request)
    {
        $totalQuestions = max(1, $exam->examQuestions()->count());

        $query = $exam->examSessions()
            ->with('student')
            ->withCount(['answers as answered_count' => function ($q) {
                $q->where(function ($q) {
                    $q->whereNotNull('selected_option_id')->orWhereNotNull('answer_text');
                });
            }]);

        if ($search = trim((string) $request->query('search'))) {
            $query->whereHas('student', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('school_id', 'like', "%{$search}%"));
        }

        return $query->get()
            ->map(function ($session) use ($exam, $totalQuestions) {
                $remaining = null;
                $isActive = in_array($session->status, [ExamSessionStatus::InProgress, ExamSessionStatus::Flagged], true);
                if ($isActive && $session->started_at) {
                    $elapsed = now()->diffInSeconds($session->started_at);
                    $remaining = max(0, ($exam->duration_minutes * 60) - $elapsed);
                }

                return [
                    'session_id' => $session->id,
                    'name' => $session->student?->name ?? '—',
                    'school_id' => $session->student?->school_id ?? '—',
                    'status' => $session->status,
                    'answered' => $session->answered_count,
                    'progress' => (int) round(($session->answered_count / $totalQuestions) * 100),
                    'remaining' => $remaining,
                    'submitted_at' => $session->submitted_at,
                ];
            })
            ->sortBy('name')
            ->values();
    }
}
