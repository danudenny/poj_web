<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $operating_unit_corporate_id
 * @property int $user_id
 *
 * Relations:
 * @property-read OperatingUnitCorporate $operatingUnitCorporate
 * @property-read User $user
 */
class OperatingUnitUser extends Model
{
    use HasFactory;

    public function operatingUnitCorporate() {
        return $this->belongsTo(OperatingUnitCorporate::class, 'operating_unit_corporate_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
