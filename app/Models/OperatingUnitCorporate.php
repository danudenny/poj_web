<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $operating_unit_relation_id
 * @property int $corporate_relation_id
 *
 * Relations:
 * @property-read Unit $operatingUnit
 * @property-read Unit $corporate
 * @property-read OperatingUnitKanwil[] $operatingUnitKanwils
 */
class OperatingUnitCorporate extends Model
{
    use HasFactory;

    protected $with = [
        'corporate',
        'operatingUnitKanwils'
    ];

    protected $appends = [
        'corporate_name'
    ];

    public function getCorporateNameAttribute() {
        /**
         * @var Unit $corporate
         */
        $corporate = $this->corporate()->first();

        return $corporate->name;
    }

    public function getCorporateAttribute() {
        return $this->corporate;
    }

    public function getOperatingUnitKanwilsAttribute() {
        return $this->operatingUnitKanwils;
    }

    public function operatingUnit() {
        return $this->belongsTo(Unit::class, 'operating_unit_relation_id', 'relation_id')
            ->where('units.unit_level', '=', Unit::UnitLevelOperatingUnit);
    }

    public function corporate() {
        return $this->belongsTo(Unit::class, 'corporate_relation_id', 'relation_id')
            ->where('units.unit_level', '=', Unit::UnitLevelCorporate);
    }

    public function operatingUnitKanwils() {
        return $this->hasMany(OperatingUnitKanwil::class, 'operating_unit_corporate_id');
    }
}
