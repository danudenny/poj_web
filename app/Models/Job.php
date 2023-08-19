<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Attributes:
 * @property-read int $id
 * @property int $odoo_job_id
 * @property string $name
 * @property Role $roles
 */
class Job extends Authenticatable
{
    use HasFactory, HasRoles;
    protected string $guard_name = 'web';

    public function hasPermissionName(string $permissionName): bool {
        foreach ($this->roles->flatMap->permissions as $permission) {
            if ($permission->name == $permissionName) {
                return true;
            }
        }

        return false;
    }

    public function employees(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'job_id', 'odoo_job_id');
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_jobs')
            ->withPivot('is_camera', 'is_upload', 'is_reporting', 'is_mandatory_reporting', 'total_reporting');
    }

    public function roles(): BelongsToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function unitJob(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_has_jobs', 'odoo_job_id', 'unit_relation_id', 'odoo_job_id', 'relation_id');
    }
}
