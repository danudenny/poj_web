<?php

namespace App\Models;

use Carbon\Carbon;
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

    protected $appends = [
        'start_time_with_timezone',
        'end_time_with_timezone',
    ];

    /**
     * @return string|null
     */
    public function getStartTimeWithTimezoneAttribute(): string|null {
        return Carbon::parse($this->start_time)->setTimezone($this->overtime->timezone)->format('Y-m-d H:i:s');
    }

    /**
     * @return string|null
     */
    public function getEndTimeWithTimezoneAttribute(): string|null {
        return Carbon::parse($this->end_time)->setTimezone($this->overtime->timezone)->format('Y-m-d H:i:s');
    }

    public function overtime() {
        return $this->belongsTo(Overtime::class);
    }

    public function overtimeEmployees() {
        return $this->hasMany(OvertimeEmployee::class, 'overtime_date_id');
    }
}
