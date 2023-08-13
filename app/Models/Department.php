<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @method static find($id)
 */
class Department extends Model
{
    use HasFactory;

    protected $hidden = [
        'pivot',
        'create_date',
        'write_date',
    ];

    public function unit(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_id')->distinct();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'odoo_department_id', 'department_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'department_has_teams')->withPivot('unit_id');
    }
}
