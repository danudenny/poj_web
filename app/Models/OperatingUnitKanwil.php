<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $operating_unit_corporate_id
 * @property int $kanwil_relation_id
 *
 * Relations:
 * @property-read OperatingUnitCorporate $operatingUnitCorporate
 * @property-read Unit $kanwil
 */
class OperatingUnitKanwil extends Model
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
        return $this->belongsTo(Unit::class, 'kanwil_relation_id', 'relation_id')
            ->where('units.unit_level', '=', Unit::UnitLevelKanwil);
    }
}
