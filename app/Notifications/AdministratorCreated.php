<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Sent to other Super Admins when a new guidance administrator account is created.
 */
class AdministratorCreated extends Notification
{
    public function __construct(protected User $administrator) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Administrator Created',
            'message' => "A new guidance administrator account was created for {$this->administrator->name}.",
            'icon' => 'person_add',
            'url' => route('admin.administrators.index'),
        ];
    }
}
