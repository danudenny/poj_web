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
        $role = $this->roles;
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

        /**
         * @var Employee $employee
         */
        $employee = $this->employee;
        $lastUnit = $this->employee->last_unit;
	    $jobs = $lastUnit->jobs;
        $listJobs = [];

        foreach ($jobs as $job) {
            if ($job->odoo_job_id === $employee->job_id) {
                $listJobs[] = [
                    'is_camera' => $job->pivot->is_camera,
                    'is_upload' => $job->pivot->is_upload,
                    'is_reporting' => $job->pivot->is_mandatory_reporting,
                    'total_reporting' => $job->pivot->total_normal,
                    'total_normal' => $job->pivot->total_normal,
                    'total_backup' => $job->pivot->total_backup,
                    'total_overtime' => $job->pivot->total_overtime,
                ];
            }
        }

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
            'misc' => $listJobs,
            'active_units' => $activeAdminUnit,
            'allowed_operating_units' => $this->allowedOperatingUnits,
            'default_operating_unit' => $employee->defaultOperatingUnit
        ];
    }
}
