<?php

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Notifications\Notification;

/**
 * Sent to the exam's creator when they publish it.
 */
class ExaminationPublished extends Notification
{
    public function __construct(protected Exam $exam) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Examination Published',
            'message' => "\"{$this->exam->title}\" has been published and is now live.",
            'icon' => 'campaign',
            'url' => route('counselor.exams.edit', $this->exam),
        ];
    }
}
