<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Attributes:
 * @property-read int $id
 * @property string $role_level
 * @property int $priority
 * @property mixed $is_active
 * @method map(Closure $param)
 */
class Role extends SpatieRole
{
    const RoleSuperAdministrator = "superadmin";
    const RoleAdmin = "admin_unit";
    const RoleStaff = "staff";
    const RoleAdminOperatingUnit = "admin_operating_unit";

    protected $appends = ['status'];
    protected $dates = ['deleted_at'];
    protected $fillable = ['name', 'guard_name', 'is_active'];
    protected $hidden = [
        'pivot',
    ];

    public function getStatusAttribute(): string
    {
        if ($this->is_active) {
            return "Active";
        } else {
            return "Inactive";
        }
    }

    public function getNameAttribute($value): string
    {
        return $value;
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id');
    }
}
