<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            CourseSeeder::class,
            YearLevelSeeder::class,
            UserSeeder::class,
            SystemSettingSeeder::class,
            QuestionBankSeeder::class,
            ExamSeeder::class,
            ExamSessionSeeder::class,
            DashboardDemoDataSeeder::class,
            CounselingNoteSeeder::class,
            AuditLogSeeder::class,
            NotificationSeeder::class,
            ReportScheduleSeeder::class,
        ]);
    }
}
