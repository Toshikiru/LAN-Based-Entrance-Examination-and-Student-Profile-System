<?php

namespace App\Notifications;

use App\Models\ExamSession;
use Illuminate\Notifications\Notification;

/**
 * Sent to the exam's creator when a student submits their attempt.
 */
class ExaminationSubmitted extends Notification
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
            'title' => 'Examination Submitted',
            'message' => "{$studentName} submitted \"{$exam?->title}\".",
            'icon' => 'task_alt',
            'url' => $exam ? route('counselor.exams.results', $exam) : null,
        ];
    }
}
