<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role;

class ExtendRole extends Role
{
    use HasFactory;

    protected $table = 'roles';
    protected $fillable = [
        'name',
        'role_level',
        'priority',
        'guard_name',
        'created_at',
        'updated_at',
    ];
}
