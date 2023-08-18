<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @method getHighestRole($role)
 * @property Employee $employee
 */
class UserMobileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $role = $this->employee->job->roles;
        $availableRole = $role->map(function ($role) {
            return $role->name;
        });

        $roleLevel = $this->getHighestRole($role);
        $permission = $roleLevel->permissions;

        $permissionName = $permission->map(function ($permission) {
            return strtolower($permission->name);
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_new' => $this->is_new,
            'customer_id' => $this->employee->customer_id,
            'is_normal_checkin' => $this->is_normal_checkin,
            'is_event_checkin' => $this->is_event_checkin,
            'is_backup_checkin' => $this->is_backup_checkin,
            'is_overtime_checkin' => $this->is_overtime_checkin,
            'is_longshift_checkin' => $this->is_longshift_checkin,
            'is_normal_checkout' => $this->is_normal_checkout,
            'is_event_checkout' => $this->is_event_checkout,
            'is_backup_checkout' => $this->is_backup_checkout,
            'is_overtime_checkout' => $this->is_overtime_checkout,
            'is_longshift_checkout' => $this->is_longshift_checkout,
            'last_units' => $this->employee->last_unit,
            'availableRole' => $availableRole,
            'roles' => $roleLevel->name,
            'permissions' => $permissionName,
        ];
    }
}
