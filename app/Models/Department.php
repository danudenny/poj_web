<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @method static find($id)
 * @method static leftJoin(string $string, string $string1, string $string2, string $string3)
 * @method static select(string[] $array)
 * @method static where(string $string, $id)
 * @method static whereHas(string $string, \Closure $param)
 */
class Department extends Model
{
    use HasFactory;

    protected $hidden = [
        'pivot',
        'create_date',
        'write_date',
    ];

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'department_has_teams')
            ->withPivot('team_id', 'unit_level');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'odoo_department_id', 'department_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'department_has_teams')->withPivot('team_id', 'unit_level');
    }
}
