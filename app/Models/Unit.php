<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

/**
 * Attributes:
 * @property-read int $id
 * @property int $relation_id
 * @property float $lat
 * @property float $long
 * @property float $radius
 */
class Unit extends Model
{
    use HasFactory;
    use QueriesExpressions;

    protected $fillable = [
        'name',
        'value',
        'unit_level',
        'parent_unit_id',
        'is_active'
    ];

    protected $casts = [
        'create_date'  => 'date:Y-m-d',
        'write_date'  => 'date:Y-m-d',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(UnitLevel::class, 'unit_level', 'value');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_unit_id', 'relation_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_unit_id', 'relation_id');
    }

    public function workLocations(): HasMany
    {
        return $this->hasMany(WorkLocation::class, 'reference_id', 'id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'id', 'unit_key');
    }

    public function department(): HasOne
    {
        return $this->hasOne(Department::class, 'company_id', 'id');
    }
}
