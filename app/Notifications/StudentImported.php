<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Sent to Super Admins for visibility whenever a new student profile is
 * added. Named to match the requested notification type — this app has no
 * separate bulk "import" feature, so student creation is the real trigger.
 */
class StudentImported extends Notification
{
    public function __construct(protected User $student) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Student Added',
            'message' => "{$this->student->name} ({$this->student->school_id}) was added to the system.",
            'icon' => 'group_add',
            'url' => route('admin.dashboard'),
        ];
    }
}
