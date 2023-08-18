<?php

namespace App\Http\Resources;

use App\Models\AdminUnit;
use App\Models\Employee;
use App\Models\User;
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
        $roleHeader = $request->header('X-Selected-Role');
        $timesheet = [];
        $role = $this->employee->job->roles;
        $availableRole = $role->map(function ($role) {
            return $role->name;
        });
        $role = $role->filter(function ($role) use ($roleHeader) {
            return $role->name === $roleHeader;
        })->first();

        $permission = $role->permissions ?? [];
        $permissionName = $permission->map(function ($permission) {
            return strtolower($permission->name);
        });
        $schedule = $this->employee->timesheetSchedules;
        $overtime = $this->employee->overtime;
        $backup = $this->employee->backup;

        if (count($schedule) > 0) {
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
        }

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

        $lastUnit = $this->employee->last_unit;
	    $job = $lastUnit->jobs->map(function ($job) use ($lastUnit) {
            $data = [
                'is_camera' => $job->pivot->is_camera,
                'is_upload' => $job->pivot->is_upload,
                'is_reporting' => $job->pivot->is_mandatory_reporting,
                'total_reporting' => $job->pivot->total_reporting,
            ];

            return array_filter($data, fn ($value) => !is_null($value) && $value !== '');
        });

        /**
         * @var Employee $employee
         */
        $employee = $this->employee;

        $activeAdminUnit = [];
        $activeAdminUnit[] = [
            'unit_relation_id' => $employee->getLastUnit()->relation_id,
            'name' => $employee->getLastUnit()->name . " (Default)"
        ];

        /**
         * @var AdminUnit[] $adminUnits
         */
        $adminUnits = AdminUnit::query()
            ->where('employee_id', '=', $employee->id)
            ->where('is_active', '=', true)
            ->get();

        foreach ($adminUnits as $adminUnit) {
            $activeAdminUnit[] = [
                'unit_relation_id' => $adminUnit->unit_relation_id,
                'name' => $adminUnit->unit->name
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_new' => $this->is_new,
            'fcm_token' => $this->fcm_token,
            'availableRole' => $availableRole,
            'roles' => $role->name,
            'permissions' => $permissionName,
            'employee_id' => $this->employee_id,
            'is_normal_checkin' => $this->is_normal_checkin,
            'is_backup_checkin' => $this->is_backup_checkin,
            'is_overtime_checkin' => $this->is_overtime_checkin,
            'is_event_checkin' => $this->is_event_checkin,
            'is_normal_checkout' => $this->is_normal_checkout,
            'is_backup_checkout' => $this->is_backup_checkout,
            'is_overtime_checkout' => $this->is_overtime_checkout,
            'is_event_checkout' => $this->is_event_checkout,
            'current_work' => $this->employee->partner ? $this->employee->partner->name : '-',
            'employee' => $this->employee->unsetRelation('timesheetSchedules')->unsetRelation('overtime')->unsetRelation('backup')->unsetRelation('job'),
            'time_schedules' => $timesheet,
            'corporate' => $this->employee->corporate,
            'job' => $this->employee->job,
            'misc' => array_filter($job->all()),
            'active_units' => $activeAdminUnit
        ];
    }
}
