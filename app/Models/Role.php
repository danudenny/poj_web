<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Attributes:
 * @property-read int $id
 * @property string $role_level
 * @property int $priority
 */
class Role extends SpatieRole
{
    const RoleSuperAdministrator = "superadmin";
    const RoleAdmin = "admin";
    const RoleStaff = "staff";
    const RoleAdminOperatingUnit = "admin_operating_unit";
    const RoleAdminBranch = "admin_branch";

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
            return "In Active";
        }
    }

    public function getNameAttribute($value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id');
    }
}
