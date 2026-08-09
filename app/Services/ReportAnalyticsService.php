<?php

namespace App\Services;

use App\Enums\ExamSessionStatus;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Institutional Reports & Performance Analytics — every figure here is
 * computed from real Exam/ExamSession/ExamAnswer data for the selected date
 * range. No simulated trend lines or invented psychometric constructs.
 */
class ReportAnalyticsService
{
    public const RANGES = [
        '30d' => 'Last 30 Days',
        'quarter' => 'Last Quarter',
        'ytd' => 'Year to Date',
    ];

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rangeBounds(string $range): array
    {
        $end = now();

        $start = match ($range) {
            'quarter' => now()->subMonths(3)->startOfDay(),
            'ytd' => now()->startOfYear(),
            default => now()->subDays(30)->startOfDay(),
        };

        return [$start, $end];
    }

    /**
     * @return array{pass_rate: float, total_exams_taken: int, avg_difficulty: ?float, flagged_rate: float}
     */
    public function kpis(string $range): array
    {
        [$start, $end] = $this->rangeBounds($range);

        $completedQuery = fn () => ExamSession::query()
            ->where('status', ExamSessionStatus::Completed)
            ->whereBetween('submitted_at', [$start, $end]);

        $completedCount = $completedQuery()->count();
        $passedCount = $completedQuery()->whereHas('result', fn ($q) => $q->where('passed', true))->count();

        $allInRangeQuery = fn () => ExamSession::query()->whereBetween('started_at', [$start, $end]);
        $allCount = $allInRangeQuery()->count();
        $flaggedCount = $allInRangeQuery()->whereIn('status', [ExamSessionStatus::Flagged, ExamSessionStatus::Terminated])->count();

        $pValues = $this->answeredQuestionStats($start, $end)->pluck('p_value')->filter(fn ($p) => $p !== null);

        return [
            'pass_rate' => $completedCount > 0 ? round(($passedCount / $completedCount) * 100, 1) : 0.0,
            'total_exams_taken' => $completedCount,
            'avg_difficulty' => $pValues->isNotEmpty() ? round($pValues->avg(), 2) : null,
            'flagged_rate' => $allCount > 0 ? round(($flaggedCount / $allCount) * 100, 1) : 0.0,
        ];
    }

    /**
     * A single real performance-trend series (no fabricated comparison
     * period) — weekly buckets for the 30-day range, monthly otherwise.
     *
     * @return array{labels: array<int, string>, pass_rates: array<int, float>, volumes: array<int, int>}
     */
    public function performanceTrend(string $range): array
    {
        [$start, $end] = $this->rangeBounds($range);
        $byWeek = $range === '30d';

        $sessions = ExamSession::query()
            ->where('status', ExamSessionStatus::Completed)
            ->whereBetween('submitted_at', [$start, $end])
            ->with('result')
            ->get();

        $labels = [];
        $passRates = [];
        $volumes = [];

        $cursor = $byWeek ? $start->copy()->startOfWeek() : $start->copy()->startOfMonth();

        while ($cursor <= $end) {
            $bucketEnd = $byWeek ? $cursor->copy()->endOfWeek() : $cursor->copy()->endOfMonth();

            $inBucket = $sessions->filter(fn (ExamSession $s) => $s->submitted_at->between($cursor, $bucketEnd));
            $passed = $inBucket->filter(fn (ExamSession $s) => (bool) $s->result?->passed)->count();

            $labels[] = $byWeek ? $cursor->format('M d') : $cursor->format('M Y');
            $volumes[] = $inBucket->count();
            $passRates[] = $inBucket->isNotEmpty() ? round(($passed / $inBucket->count()) * 100, 1) : 0.0;

            $cursor = $byWeek ? $cursor->addWeek() : $cursor->addMonth();
        }

        return ['labels' => $labels, 'pass_rates' => $passRates, 'volumes' => $volumes];
    }

    /**
     * Item-analysis difficulty distribution (Easy/Optimal/Hard/Poor buckets
     * by empirical p-value), for the "Item Analysis" donut widget.
     *
     * @return array{counts: array<string, int>, percentages: array<string, float>, total_questions: int}
     */
    public function difficultyDistribution(string $range): array
    {
        [$start, $end] = $this->rangeBounds($range);

        $buckets = ['easy' => 0, 'optimal' => 0, 'hard' => 0, 'poor' => 0];

        foreach ($this->answeredQuestionStats($start, $end) as $stat) {
            if ($stat['p_value'] === null) {
                continue;
            }
            $buckets[$this->bucketKey($stat['p_value'])]++;
        }

        $total = array_sum($buckets);
        $percentages = [];
        foreach ($buckets as $key => $count) {
            $percentages[$key] = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;
        }

        return ['counts' => $buckets, 'percentages' => $percentages, 'total_questions' => $total];
    }

    /**
     * Per-student rollup for the "Student Performance Summary" export.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function studentPerformanceSummaryRows(string $range): Collection
    {
        [$start, $end] = $this->rangeBounds($range);

        return User::query()
            ->where('role', UserRole::Student)
            ->get()
            ->map(function (User $student) use ($start, $end) {
                $sessions = $student->examSessions()
                    ->where('status', ExamSessionStatus::Completed)
                    ->whereBetween('submitted_at', [$start, $end])
                    ->with('result')
                    ->get();

                if ($sessions->isEmpty()) {
                    return null;
                }

                $passed = $sessions->filter(fn (ExamSession $s) => (bool) $s->result?->passed)->count();

                return [
                    'name' => $student->name,
                    'school_id' => $student->school_id,
                    'exams_taken' => $sessions->count(),
                    'average_percentage' => round($sessions->avg(fn (ExamSession $s) => (float) ($s->result?->percentage ?? 0)), 1),
                    'pass_rate' => round(($passed / $sessions->count()) * 100, 1),
                ];
            })
            ->filter()
            ->sortByDesc('average_percentage')
            ->values();
    }

    /**
     * Per-exam descriptive statistics for the "Exam Statistics (Deep Dive)" export.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function examStatisticsRows(string $range): Collection
    {
        [$start, $end] = $this->rangeBounds($range);

        return Exam::query()
            ->whereHas('examSessions', fn ($q) => $q
                ->where('status', ExamSessionStatus::Completed)
                ->whereBetween('submitted_at', [$start, $end]))
            ->get()
            ->map(function (Exam $exam) use ($start, $end) {
                $sessions = $exam->examSessions()
                    ->where('status', ExamSessionStatus::Completed)
                    ->whereBetween('submitted_at', [$start, $end])
                    ->with('result')
                    ->get();

                $percentages = $sessions->map(fn (ExamSession $s) => (float) ($s->result?->percentage ?? 0))->sort()->values();
                $n = $percentages->count();
                $mean = $n > 0 ? $percentages->avg() : 0.0;
                $median = $n > 0
                    ? ($n % 2 === 0 ? ($percentages[$n / 2 - 1] + $percentages[$n / 2]) / 2 : $percentages[intdiv($n, 2)])
                    : 0.0;
                $variance = $n > 0
                    ? $percentages->reduce(fn ($carry, $p) => $carry + ($p - $mean) ** 2, 0.0) / $n
                    : 0.0;
                $passed = $sessions->filter(fn (ExamSession $s) => (bool) $s->result?->passed)->count();

                return [
                    'title' => $exam->title,
                    'n' => $n,
                    'mean' => round($mean, 1),
                    'median' => round($median, 1),
                    'stdev' => round(sqrt($variance), 1),
                    'pass_rate' => $n > 0 ? round(($passed / $n) * 100, 1) : 0.0,
                ];
            })
            ->values();
    }

    /**
     * Per-question item analysis for the "Item Analysis Detail" export (CSV only).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function itemAnalysisRows(string $range): Collection
    {
        [$start, $end] = $this->rangeBounds($range);
        $stats = $this->answeredQuestionStats($start, $end);
        $questions = Question::query()->whereIn('id', $stats->pluck('question_id'))->get()->keyBy('id');

        return $stats->map(function (array $stat) use ($questions) {
            $question = $questions->get($stat['question_id']);

            return [
                'question_text' => $question?->question_text ?? '—',
                'category' => $question?->section_category ?? '—',
                'type' => $question?->type?->label() ?? '—',
                'authored_difficulty' => $question?->difficulty ? ucfirst($question->difficulty->value) : '—',
                'times_answered' => $stat['total'],
                'p_value' => $stat['p_value'],
                'bucket' => $stat['p_value'] !== null ? ucfirst($this->bucketKey($stat['p_value'])) : '—',
            ];
        })
            ->sortBy('p_value')
            ->values();
    }

    /**
     * Per-question answer counts (total answered, correct) for questions
     * answered within sessions submitted in the given range.
     *
     * @return Collection<int, array{question_id: int, total: int, correct: int, p_value: ?float}>
     */
    protected function answeredQuestionStats(Carbon $start, Carbon $end): Collection
    {
        return ExamAnswer::query()
            ->whereNotNull('is_correct')
            ->whereHas('examSession', fn ($q) => $q
                ->where('status', ExamSessionStatus::Completed)
                ->whereBetween('submitted_at', [$start, $end]))
            ->selectRaw('question_id, COUNT(*) as total, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            ->groupBy('question_id')
            ->get()
            ->map(fn ($row) => [
                'question_id' => (int) $row->question_id,
                'total' => (int) $row->total,
                'correct' => (int) $row->correct,
                'p_value' => $row->total > 0 ? round($row->correct / $row->total, 3) : null,
            ]);
    }

    protected function bucketKey(float $p): string
    {
        return match (true) {
            $p >= 0.8 => 'easy',
            $p >= 0.4 => 'optimal',
            $p >= 0.2 => 'hard',
            default => 'poor',
        };
    }
}
