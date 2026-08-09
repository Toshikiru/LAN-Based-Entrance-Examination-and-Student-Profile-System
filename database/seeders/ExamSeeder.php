<?php

namespace Database\Seeders;

use App\Enums\ExamCategory;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        if (Exam::count() > 0) {
            return;
        }

        $counselor = User::where('role', UserRole::Counselor)->first();

        $exam = Exam::create([
            'title' => 'College Entrance Aptitude Exam 2024',
            'description' => 'Comprehensive entrance examination covering quantitative, verbal, and logical reasoning.',
            'category' => ExamCategory::Entrance,
            'duration_minutes' => 120,
            'passing_score' => 60,
            'negative_marking' => true,
            'status' => ExamStatus::Published,
            'created_by' => $counselor->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
        ]);

        $sectionA = $exam->sections()->create(['title' => 'Section A: Verbal Reasoning', 'order' => 1]);
        $sectionB = $exam->sections()->create(['title' => 'Section B: Quantitative Reasoning', 'order' => 2]);
        $sectionC = $exam->sections()->create(['title' => 'Section C: Logical Reasoning', 'order' => 3]);

        $sectionMap = [
            'Verbal Reasoning' => $sectionA,
            'Quantitative Reasoning' => $sectionB,
            'Logical Reasoning' => $sectionC,
        ];

        $order = 1;
        foreach (Question::whereIn('section_category', array_keys($sectionMap))->get() as $question) {
            $exam->examQuestions()->create([
                'question_id' => $question->id,
                'exam_section_id' => $sectionMap[$question->section_category]->id,
                'order' => $order++,
            ]);
        }
    }
}
