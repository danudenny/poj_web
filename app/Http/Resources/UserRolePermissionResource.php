<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRolePermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $role = $this->roles->pluck('name');
        $permission = $this->roles->map(function ($role) {
            return $role->permissions;
        })->collapse()->pluck('name')->unique()->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_new' => $this->is_new,
            'unit_id' => $this->employee->unit_id,
            'roles' => $role,
            'permissions' => $permission
        ];
    }
}
