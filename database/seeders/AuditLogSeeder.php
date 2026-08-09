<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        if (AuditLog::count() > 0) {
            return;
        }

        $counselor = User::where('role', UserRole::Counselor)->first();
        $admin = User::where('role', UserRole::SuperAdmin)->first();
        $exam = Exam::first();
        $question = Question::first();

        if (! $counselor || ! $admin) {
            return;
        }

        $entries = [
            ['user' => $counselor, 'action' => 'exam.created', 'subject' => $exam, 'description' => "Created examination \"{$exam?->title}\".", 'daysAgo' => 10],
            ['user' => $counselor, 'action' => 'question.created', 'subject' => $question, 'description' => 'Created a new question in the bank.', 'daysAgo' => 9],
            ['user' => $counselor, 'action' => 'exam.published', 'subject' => $exam, 'description' => "Published \"{$exam?->title}\".", 'daysAgo' => 7],
            ['user' => $counselor, 'action' => 'student.created', 'subject' => null, 'description' => 'Added a new student profile.', 'daysAgo' => 6],
            ['user' => $admin, 'action' => 'administrator.created', 'subject' => $counselor, 'description' => 'Created a guidance administrator account.', 'daysAgo' => 30],
            ['user' => $counselor, 'action' => 'exam.graded', 'subject' => $exam, 'description' => 'Graded short-answer items for a completed submission.', 'daysAgo' => 1],
        ];

        foreach ($entries as $entry) {
            AuditLog::create([
                'user_id' => $entry['user']->id,
                'action' => $entry['action'],
                'description' => $entry['description'],
                'subject_type' => $entry['subject'] ? get_class($entry['subject']) : null,
                'subject_id' => $entry['subject']?->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
                'created_at' => now()->subDays($entry['daysAgo']),
            ]);
        }
    }
}
