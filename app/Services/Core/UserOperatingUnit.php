<?php

namespace App\Services\Core;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property int $user_id
 * @property string $unit_relation_id
 *
 * Relations:
 * @property-read User $user
 * @property-read Unit $operatingUnit
 */
class UserOperatingUnit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = 'user_id';

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function operatingUnit() {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }
}
