<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $operating_unit_corporate_id
 * @property int $unit_relation_id
 * @property int $unit_level
 *
 * Relations:
 * @property-read OperatingUnitCorporate $operatingUnitCorporate
 * @property-read Unit $kanwil
 */
class OperatingUnitDetail extends Model
{
    use HasFactory;

    protected $appends = [
        'kanwil'
    ];

    public function getKanwilAttribute() {
        return $this->kanwil()->first();
    }

    public function operatingUnitCorporate() {
        return $this->belongsTo(OperatingUnitCorporate::class, 'operating_unit_corporate_id');
    }

    public function kanwil() {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id')
            ->where('units.unit_level', '=', $this->unit_level);
    }
}
