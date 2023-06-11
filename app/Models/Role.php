<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;
class Role extends SpatieRole
{
    use SoftDeletes;
    protected $appends = ['status'];
    protected $dates = ['deleted_at'];
    protected $fillable = ['name', 'guard_name', 'is_active'];

    public function getStatusAttribute()
    {
        if ($this->is_active) {
            return "Active";
        } else {
            return "In Active";
        }
    }
}
