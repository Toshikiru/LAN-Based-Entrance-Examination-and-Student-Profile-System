<?php

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Notifications\Notification;

/**
 * Sent to a student once their result for an exam is final and visible
 * (either immediately at submission for fully auto-graded exams, or after
 * a counselor finishes manual grading).
 */
class ResultsReleased extends Notification
{
    public function __construct(protected Exam $exam) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Results Released',
            'message' => "Your results for \"{$this->exam->title}\" are now available.",
            'icon' => 'grade',
            'url' => route('student.exams.result', $this->exam),
        ];
    }
}
