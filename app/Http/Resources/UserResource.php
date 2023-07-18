<?php

namespace App\Http\Resources;

use App\Helpers\UnitHelper;
use App\Models\Employee;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        $auth = auth()->user();
        $role = $this->roles->pluck('name');
        $permission = $this->roles->map(function ($role) {
            return $role->permissions;
        })->collapse()->pluck('name')->unique()->values();
        $empUnit = $this->employee;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_new' => $this->is_new,
            'fcm_token' => $this->fcm_token,
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
            'roles' =>$role,
            'permissions' => $permission,
            'jobs' => $empUnit,
        ];
    }
}
