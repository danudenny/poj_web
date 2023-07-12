<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMobileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_new' => $this->is_new,
            'kanwil_id' => $this->employee->kanwil_id,
            'area_id' => $this->employee->area_id,
            'cabang_id' => $this->employee->cabang_id,
            'outlet_id' => $this->employee->outlet_id,
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
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}
