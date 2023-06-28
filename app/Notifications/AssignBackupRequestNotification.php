<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class AssignBackupRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $fcmTokens;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($fcmTokens)
    {;
        $this->fcmTokens = $fcmTokens;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['firebase'];
    }

    public function toFirebase($notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle('Assign Backup Request')
            ->withBody('You have been assigned a backup request. Check your app for more details.')
            ->withPriority('high')->asMessage($this->fcmTokens);
    }
}
