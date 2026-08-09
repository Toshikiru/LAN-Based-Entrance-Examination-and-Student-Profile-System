<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CounselingNote;
use App\Models\User;
use Illuminate\Database\Seeder;

class CounselingNoteSeeder extends Seeder
{
    public function run(): void
    {
        if (CounselingNote::count() > 0) {
            return;
        }

        $counselor = User::where('role', UserRole::Counselor)->first();
        $students = User::where('role', UserRole::Student)->orderBy('id')->get();

        if (! $counselor || $students->isEmpty()) {
            return;
        }

        $notes = [
            [
                'student' => $students->get(0),
                'category' => 'academic',
                'note_date' => now()->subDays(20),
                'content' => 'Discussed recent decline in quiz scores. Student attributed it to part-time work schedule. Agreed on a revised study plan.',
                'follow_up_date' => now()->addDays(10),
                'status' => 'open',
            ],
            [
                'student' => $students->get(1),
                'category' => 'career',
                'note_date' => now()->subDays(15),
                'content' => 'Career guidance session covering post-graduation options. Student is interested in pursuing a licensure exam review program.',
                'follow_up_date' => null,
                'status' => 'closed',
            ],
            [
                'student' => $students->get(2),
                'category' => 'behavioral',
                'note_date' => now()->subDays(8),
                'content' => 'Reported difficulty adjusting to group work in class. Discussed communication strategies with peers.',
                'follow_up_date' => now()->addDays(14),
                'status' => 'open',
            ],
            [
                'student' => $students->get(3),
                'category' => 'personal',
                'note_date' => now()->subDays(3),
                'content' => 'Student requested a confidential conversation regarding a family matter affecting attendance. Provided referral to the school\'s support office.',
                'follow_up_date' => now()->addDays(7),
                'status' => 'open',
            ],
        ];

        foreach ($notes as $note) {
            if (! $note['student']) {
                continue;
            }

            CounselingNote::create([
                'student_id' => $note['student']->id,
                'counselor_id' => $counselor->id,
                'category' => $note['category'],
                'note_date' => $note['note_date'],
                'content' => $note['content'],
                'follow_up_date' => $note['follow_up_date'],
                'status' => $note['status'],
            ]);
        }
    }
}
