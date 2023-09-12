<?php

namespace App\Helpers\Notification;

class NotificationScreen
{
    const MobileJadwalKehadiran = "JadwalKehadiran";
    const MobileOvertimeList = "OvertimeList";
    const MobileBackupList = "BackupList";
    const MobileLeavePermissionLeave = "LeavePermissionList";
    const MobileTinjauanKehadiran = "TinjauanKehadiran";
    const ChangePassword = "ChangePassword";


    private string $screenName;
    private array $payload;

    public function __construct(string $screenName, array $payload = [])
    {
        $this->screenName = $screenName;
        $this->payload = $payload;
    }

    public function buildMobilePayload() {
        return [
            'screen_name' => $this->screenName,
            'payload' => $this->payload
        ];
    }
}
