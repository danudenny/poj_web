<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRolePermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $timesheet = [];
        $role = $this->roles->pluck('name');
        $permission = $this->roles->map(function ($role) {
            return $role->permissions;
        })->collapse()->pluck('name')->unique()->values();
        $schedule = $this->employee->timesheetSchedules;
        $overtime = $this->employee->overtime;
        $backup = $this->employee->backup;

        $periods = $schedule->map(function ($schedule) {
            $timezone = getTimezoneV2(floatval($this->employee->last_unit->lat), floatval($this->employee->last_unit->long));
            $scheduleDate = Carbon::createFromDate($schedule->period->year, $schedule->period->month, $schedule->date, $timezone);

            return [
                'date' => $scheduleDate->format('d F Y'),
                'name' => $schedule->timesheet->name,
                'start_time' => $schedule->timesheet->start_time,
                'end_time' => $schedule->timesheet->end_time,
            ];
        })->unique()->values();

        $timesheet['daily'] = $periods->map(function ($period) {
            return [
                'date' => $period['date'],
                'name' => $period['name'],
                'start_time' => $period['start_time'],
                'end_time' => $period['end_time'],
            ];
        });

        $overtimeDate = $overtime->map(function ($overtime) {
            return $overtime->overtimeDate;
        })->unique()->values();

        $timesheet['overtime'] = $overtimeDate->map(function ($overtimeDate) {
            $timezone = getTimezoneV2(floatval($this->employee->last_unit->lat), floatval($this->employee->last_unit->long));
            return [
                'date' => Carbon::parse($overtimeDate->date, 'UTC')->addDay(1)->setTimezone($timezone)->format('d F Y'),
                'start_time' => Carbon::parse($overtimeDate->start_time, 'UTC')->setTimezone($timezone)->format('H:i'),
                'end_time' => Carbon::parse($overtimeDate->end_time, 'UTC')->setTimezone($timezone)->format('H:i'),
            ];
        })->values();

        $backupDate = $backup->map(function ($backup) {
            return $backup->backup;
        })->unique()->values();

        $timesheet['backup'] = $backupDate->map(function ($backupDate) {
            return $backupDate->backupTimes->map(function ($backupTime) {
                $timezone = getTimezoneV2(floatval($this->employee->last_unit->lat), floatval($this->employee->last_unit->long));

                return [
                    'date' => Carbon::parse($backupTime->backup_date, 'UTC')->addDay(1)->setTimezone($timezone)->format('d F Y'),
                    'start_time' => Carbon::parse($backupTime->start_time, 'UTC')->setTimezone($timezone)->format('H:i'),
                    'end_time' => Carbon::parse($backupTime->end_time, 'UTC')->setTimezone($timezone)->format('H:i'),
                ];
            });
        })->collapse()->unique()->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_new' => $this->is_new,
            'fcm_token' => $this->fcm_token,
            'roles' => $role,
            'permissions' => $permission,
            'is_normal_checkin' => $this->is_normal_checkin,
            'is_backup_checkin' => $this->is_backup_checkin,
            'is_overtime_checkin' => $this->is_overtime_checkin,
            'is_event_checkin' => $this->is_event_checkin,
            'is_normal_checkout' => $this->is_normal_checkout,
            'is_backup_checkout' => $this->is_backup_checkout,
            'is_overtime_checkout' => $this->is_overtime_checkout,
            'is_event_checkout' => $this->is_event_checkout,
            'employee' => $this->employee->unsetRelation('timesheetSchedules')->unsetRelation('overtime')->unsetRelation('backup')->unsetRelation('job'),
            'time_schedules' => $timesheet,
            'job' => $this->employee->job,
        ];
    }
}
