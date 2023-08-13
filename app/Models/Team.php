<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method paginate(int $param)
 * @method when($name, Closure $param)
 * @method create(array $array)
 * @method find($id)
 * @method where(string $string, string $string1, $id)
 * @method whereRaw(string $string)
 * @method withCount(string $string)
 */
class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    protected $hidden = [
        'pivot',
        'created_at',
        'updated_at'
    ];

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_has_teams', 'department_id');
    }
}
