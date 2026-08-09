<?php

namespace App\Notifications;

use App\Models\ExamSession;
use Illuminate\Notifications\Notification;

/**
 * Sent to the exam's creator when a submission has short-answer items
 * awaiting manual grading.
 */
class ManualGradingRequired extends Notification
{
    public function __construct(protected ExamSession $session) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $exam = $this->session->exam;
        $studentName = $this->session->student?->name ?? 'A student';

        return [
            'title' => 'Manual Grading Required',
            'message' => "{$studentName}'s submission for \"{$exam?->title}\" has short-answer items awaiting grading.",
            'icon' => 'rate_review',
            'url' => $exam ? route('counselor.exams.grading', $exam) : null,
        ];
    }
}
