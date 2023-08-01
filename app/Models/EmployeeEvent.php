<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $event_id
 * @property int $employee_id
 * @property bool $is_need_absence
 * @property string $event_datetime
 * @property string $event_date
 * @property string $event_time
 * @property string|null $check_in_time
 * @property string|null $check_in_lat
 * @property string|null $check_in_long
 * @property string|null $check_out_time
 * @property string|null $check_out_lat
 * @property string|null $check_out_long
 * @property string|null $check_in_timezone
 * @property string|null $check_out_timezone
 * @property int $employee_attendance_id
 *
 * Relations:
 * @property-read Event $event
 * @property-read Employee $employee
 * @property-read EmployeeAttendance $employeeAttendance
 */
class EmployeeEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'employee_id',
        'is_need_absence',
        'event_datetime',
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'event_date_time_with_timezone',
        'check_in_time_with_location_timezone',
        'check_out_time_with_location_timezone',
        'check_in_time_with_employee_timezone',
        'check_out_time_with_employee_timezone'
    ];

    public function getEventDateTimeWithTimezoneAttribute() {
        return Carbon::parse($this->event_datetime)->setTimezone($this->event->timezone)->format('Y-m-d H:i:s T');
    }

    /**
     * @return string|null
     */
    public function getCheckInTimeWithEmployeeTimezoneAttribute(): string|null {
        if (is_null($this->check_in_time)) {
            return null;
        }

        return Carbon::parse($this->check_in_time)->setTimezone($this->check_in_timezone)->format('Y-m-d H:i:s T');
    }

    /**
     * @return string|null
     */
    public function getCheckOutTimeWithEmployeeTimezoneAttribute(): string|null {
        if (is_null($this->check_out_time)) {
            return null;
        }

        return Carbon::parse($this->check_out_time)->setTimezone($this->check_out_timezone)->format('Y-m-d H:i:s T');
    }

    /**
     * @return string|null
     */
    public function getCheckInTimeWithLocationTimezoneAttribute(): string|null {
        if (is_null($this->check_in_time)) {
            return null;
        }

        return Carbon::parse($this->check_in_time)->setTimezone($this->event->timezone)->format('Y-m-d H:i:s T');
    }

    /**
     * @return string|null
     */
    public function getCheckOutTimeWithLocationTimezoneAttribute(): string|null {
        if (is_null($this->check_out_time)) {
            return null;
        }

        return Carbon::parse($this->check_out_time)->setTimezone($this->event->timezone)->format('Y-m-d H:i:s T');
    }

    public function event() {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function employeeAttendance(): BelongsTo {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id');
    }
}
