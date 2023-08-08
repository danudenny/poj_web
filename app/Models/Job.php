<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Attributes:
 * @property-read int $id
 * @property int $odoo_job_id
 * @property string $name
 */
class Job extends Authenticatable
{
    use HasFactory, HasRoles;
    protected $guard_name = 'web';
    public function employees(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'job_id', 'odoo_job_id');
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_jobs')
            ->withPivot('is_camera', 'is_upload', 'is_reporting', 'is_mandatory_reporting', 'total_reporting');
    }

    public function unitJob(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_jobs')
            ->withPivot('is_camera', 'is_upload', 'is_reporting', 'is_mandatory_reporting', 'total_reporting');
    }

    public function roles(): BelongsToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'role_id', 'model_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
