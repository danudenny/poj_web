<?php

namespace App\Notifications;

use App\Models\EmployeeAttendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\Exceptions\CouldNotSendNotification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FCMResourceNotification;

class AttendanceApproveNotification extends Notification implements ShouldQueue
{
    use Queueable;

    //to employee attendance notification

    protected EmployeeAttendance $attendance;

    public function __construct($attendance)
    {
        $this->attendance = $attendance;
    }

    public function via($notifiable): array
    {
        return [FcmChannel::class, 'mail'];
    }

    /**
     * @throws CouldNotSendNotification
     */
    public function toFcm($notifiable)
    {
        $fcmToken = $notifiable->fcm_token;

        return FcmMessage::create()
            ->setData(['event_id' => $this->attendance->id])
            ->setNotification(
                FCMResourceNotification::create()
                    ->setTitle('Attendance Approval')
                    ->setBody('You have an approval request for attendance: ' . $this->attendance->employee()->name)
            )
            ->setPriority('high')
            ->setDeviceToken($fcmToken);
    }
}
