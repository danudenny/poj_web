<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtendPermission extends \Spatie\Permission\Models\Permission
{
    use HasFactory;

    protected $fillable = [
        'view',
        'create',
        'edit',
        'delete',
    ];
}
