<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportScheduleSeeder extends Seeder
{
    public function run(): void
    {
        if (ReportSchedule::count() > 0) {
            return;
        }

        $counselor = User::where('role', UserRole::Counselor)->first();
        if (! $counselor) {
            return;
        }

        ReportSchedule::create([
            'report_type' => 'student_performance_summary',
            'recipient_name' => 'Guidance Office',
            'recipient_email' => 'guidance.office@tpcentrypoint.test',
            'frequency' => 'monthly',
            'is_active' => true,
            'created_by' => $counselor->id,
        ]);

        ReportSchedule::create([
            'report_type' => 'exam_statistics',
            'recipient_name' => 'Academic Affairs',
            'recipient_email' => 'academic.affairs@tpcentrypoint.test',
            'frequency' => 'quarterly',
            'is_active' => false,
            'created_by' => $counselor->id,
        ]);
    }
}
