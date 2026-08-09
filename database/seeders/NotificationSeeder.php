<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use App\Notifications\ExaminationAssigned;
use App\Notifications\ExaminationPublished;
use App\Notifications\ExaminationSubmitted;
use App\Notifications\ResultsReleased;
use Illuminate\Database\Seeder;

/**
 * Seeds a few sample notifications, via the real Notification classes, so
 * the notification dropdown isn't empty on a fresh install. Guarded so
 * re-running `db:seed` doesn't pile up duplicates.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        if (\Illuminate\Notifications\DatabaseNotification::count() > 0) {
            return;
        }

        $exam = Exam::first();
        $counselor = User::where('role', UserRole::Counselor)->first();
        $students = User::where('role', UserRole::Student)->orderBy('id')->get();

        if (! $exam || ! $counselor || $students->isEmpty()) {
            return;
        }

        $counselor->notify(new ExaminationPublished($exam));

        foreach ($students->take(3) as $student) {
            $student->notify(new ExaminationAssigned($exam));
        }

        $completedSession = ExamSession::where('exam_id', $exam->id)->where('status', 'completed')->first();

        if ($completedSession) {
            $counselor->notify(new ExaminationSubmitted($completedSession));

            if ($completedSession->student && $exam->show_score) {
                $completedSession->student->notify(new ResultsReleased($exam));
            }
        }
    }
}
