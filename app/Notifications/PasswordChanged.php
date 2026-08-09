<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Sent to an administrator when a Super Admin resets their password.
 */
class PasswordChanged extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Password Changed',
            'message' => 'Your password was reset by a system administrator.',
            'icon' => 'lock_reset',
            'url' => null,
        ];
    }
}
