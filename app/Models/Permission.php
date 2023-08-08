<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    const AssignOperatingUnit = "operating-unit.assign";
    const DeleteOperatingUnit = "operating-unit.delete";
    const AssignParentJob = "job-assign-parent";

    protected $hidden = ['pivot'];

    public function getNameAttribute($value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
