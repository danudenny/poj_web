<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExtendPermission extends \Spatie\Permission\Models\Permission
{
    use HasFactory;

    protected $fillable = [
        'group',
        'ability',
    ];

    public function setGroupAttribute($value): void
    {
        $ability = Str::afterLast(Str::lower($value), '-');
        $this->attributes['ability'] = $ability;
        $this->attributes['group'] = ucfirst(Str::before($value, '-'));
    }

}
