<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvitationAccepted extends Notification
{
    use Queueable;

    public $acceptedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct($acceptedBy)
    {
        $this->acceptedBy = $acceptedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invitation_accepted',
            'title' => 'Invitation Accepted',
            'message' => "{$this->acceptedBy->name} has accepted the invitation.",
            'user_id' => $this->acceptedBy->id,
            'user_email' => $this->acceptedBy->email,
        ];
    }
}
