<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $odoo_working_hour_id
 * @property string $name
 *
 * Relations:
 * @property-read WorkingHourDetail[] $workingHourDetails
 */
class WorkingHour extends Model
{
    use HasFactory;

    public function workingHourDetails() {
        return $this->hasMany(WorkingHourDetail::class, 'odoo_working_hour_id', 'odoo_working_hour_id');
    }
}
