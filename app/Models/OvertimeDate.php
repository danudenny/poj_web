<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $overtime_id
 * @property string $date
 * @property string $start_time
 * @property string $end_time
 *
 * Relations:
 * @property-read Overtime $overtime
 * @property-read OvertimeEmployee[] $overtimeEmployees
 */
class OvertimeDate extends Model
{
    use HasFactory;

    public function overtime() {
        return $this->belongsTo(Overtime::class);
    }

    public function overtimeEmployees() {
        return $this->hasMany(OvertimeEmployee::class, 'overtime_date_id');
    }
}
