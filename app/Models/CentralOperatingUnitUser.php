<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $user_id
 * @property int $unit_relation_id
 *
 * Relations:
 * @property-read User $user
 * @property-read Unit $unit
 */
class CentralOperatingUnitUser extends Model
{
    use HasFactory;

    protected $with = [
        'user',
        'unit'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unit() {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }
}
