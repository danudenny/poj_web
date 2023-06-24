<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserRolePermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $role = $this->roles->pluck('name');
        $permission = $this->roles->map(function ($role) {
            return $role->permissions;
        })->collapse()->pluck('name')->unique();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_new' => $this->is_new,
            'roles' => $role,
            'permissions' => $permission
        ];
    }
}
