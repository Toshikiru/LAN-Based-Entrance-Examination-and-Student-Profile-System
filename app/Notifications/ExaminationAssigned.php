<?php

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Notifications\Notification;

/**
 * Sent to each student targeted by an exam (matching department/year level)
 * when it's published — this app has no separate "assignment" step, so
 * becoming eligible to join a newly published exam is the real trigger.
 */
class ExaminationAssigned extends Notification
{
    public function __construct(protected Exam $exam) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Examination Available',
            'message' => "\"{$this->exam->title}\" is now available for you to take.",
            'icon' => 'assignment',
            'url' => route('student.exams.join'),
        ];
    }
}
