<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $kantor_perwakilan_id
 * @property int $corporate_relation_id
 *
 * Relations:
 * @property-read KantorPerwakilan $kantorPerwakilan
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

    public function kantorPerwakilan() {
        return $this->belongsTo(KantorPerwakilan::class, 'kantor_perwakilan_id');
    }

    public function corporate() {
        return $this->belongsTo(Unit::class, 'corporate_relation_id', 'relation_id');
    }

    public function operatingUnitKanwils() {
        return $this->hasMany(OperatingUnitKanwil::class, 'operating_unit_corporate_id');
    }
}
