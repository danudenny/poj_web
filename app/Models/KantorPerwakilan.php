<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $operating_unit_odoo_id
 *
 * Relations:
 * @property-read OperatingUnitCorporate[] $operatingUnitCorporates
 */
class KantorPerwakilan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'corporate_id',
        'kanwil_id',
    ];

    public function getOperatingUnitCorporatesAttribute() {
        return $this->operatingUnitCorporates()->get();
    }

    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'corporate_id');
    }

    public function kanwil(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'kanwil_id');
    }

    public function operatingUnitCorporates()
    {
        return $this->hasMany(OperatingUnitCorporate::class, 'kantor_perwakilan_id');
    }
}
