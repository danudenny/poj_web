<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserMobileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_new' => $this->is_new,
            'is_normal_checkin' => $this->is_normal_checkin,
            'is_event_checkin' => $this->is_event_checkin,
            'is_backup_checkin' => $this->is_backup_checkin,
            'is_overtime_checkin' => $this->is_overtime_checkin,
            'is_longshift_checkin' => $this->is_longshift_checkin,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}
