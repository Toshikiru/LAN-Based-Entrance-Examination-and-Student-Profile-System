<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\ReportSchedule;
use App\Services\ReportAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportAnalyticsService $analytics) {}

    public function index(Request $request): View
    {
        $range = $this->resolveRange($request);

        return view('counselor.reports.index', [
            'range' => $range,
            'ranges' => ReportAnalyticsService::RANGES,
            'kpis' => $this->analytics->kpis($range),
            'trend' => $this->analytics->performanceTrend($range),
            'difficulty' => $this->analytics->difficultyDistribution($range),
            'schedules' => ReportSchedule::query()->with('creator')->latest()->get(),
        ]);
    }

    public function studentSummaryCsv(Request $request): StreamedResponse
    {
        $rows = $this->analytics->studentPerformanceSummaryRows($this->resolveRange($request));

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student', 'School ID', 'Exams Taken', 'Average %', 'Pass Rate %']);
            foreach ($rows as $row) {
                fputcsv($out, [$row['name'], $row['school_id'], $row['exams_taken'], $row['average_percentage'], $row['pass_rate']]);
            }
            fclose($out);
        }, 'student-performance-summary.csv', ['Content-Type' => 'text/csv']);
    }

    public function studentSummaryPrint(Request $request): View
    {
        $range = $this->resolveRange($request);

        return view('counselor.reports.student-summary-print', [
            'rows' => $this->analytics->studentPerformanceSummaryRows($range),
            'rangeLabel' => ReportAnalyticsService::RANGES[$range],
        ]);
    }

    public function examStatisticsCsv(Request $request): StreamedResponse
    {
        $rows = $this->analytics->examStatisticsRows($this->resolveRange($request));

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Examination', 'Completed (n)', 'Mean %', 'Median %', 'Std. Dev.', 'Pass Rate %']);
            foreach ($rows as $row) {
                fputcsv($out, [$row['title'], $row['n'], $row['mean'], $row['median'], $row['stdev'], $row['pass_rate']]);
            }
            fclose($out);
        }, 'exam-statistics.csv', ['Content-Type' => 'text/csv']);
    }

    public function examStatisticsPrint(Request $request): View
    {
        $range = $this->resolveRange($request);

        return view('counselor.reports.exam-statistics-print', [
            'rows' => $this->analytics->examStatisticsRows($range),
            'rangeLabel' => ReportAnalyticsService::RANGES[$range],
        ]);
    }

    /**
     * Item Analysis Detail is CSV-only — a per-question breakdown is unwieldy
     * as a printed page and, per the source design, has no PDF export.
     */
    public function itemAnalysisCsv(Request $request): StreamedResponse
    {
        $rows = $this->analytics->itemAnalysisRows($this->resolveRange($request));

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Question', 'Category', 'Type', 'Authored Difficulty', 'Times Answered', 'P-Value', 'Empirical Difficulty']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['question_text'], $row['category'], $row['type'], $row['authored_difficulty'],
                    $row['times_answered'], $row['p_value'], $row['bucket'],
                ]);
            }
            fclose($out);
        }, 'item-analysis-detail.csv', ['Content-Type' => 'text/csv']);
    }

    protected function resolveRange(Request $request): string
    {
        $range = $request->query('range', '30d');

        return array_key_exists($range, ReportAnalyticsService::RANGES) ? $range : '30d';
    }
}
