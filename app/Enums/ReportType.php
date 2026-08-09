<?php

namespace App\Enums;

enum ReportType: string
{
    case StudentPerformanceSummary = 'student_performance_summary';
    case ExamStatistics = 'exam_statistics';
    case ItemAnalysis = 'item_analysis';

    public function label(): string
    {
        return match ($this) {
            self::StudentPerformanceSummary => 'Student Performance Summary',
            self::ExamStatistics => 'Exam Statistics (Deep Dive)',
            self::ItemAnalysis => 'Item Analysis Detail',
        };
    }
}
