<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property int $overtime_date_id
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
 * @property-read Employee $employee
 * @property-read OvertimeDate $overtimeDate
 * @property-read EmployeeAttendance $employeeAttendance
 */
class OvertimeEmployee extends Model
{
    use HasFactory;

    protected $appends = [
        'check_in_time_with_unit_timezone',
        'check_out_time_with_unit_timezone',
        'check_in_time_with_employee_timezone',
        'check_out_time_with_employee_timezone'
    ];

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
    public function getCheckInTimeWithUnitTimezoneAttribute(): string|null {
        if (is_null($this->check_in_time)) {
            return null;
        }

        return Carbon::parse($this->check_in_time)->setTimezone($this->overtimeDate->overtime->timezone)->format('Y-m-d H:i:s T');
    }

    /**
     * @return string|null
     */
    public function getCheckOutTimeWithUnitTimezoneAttribute(): string|null {
        if (is_null($this->check_out_time)) {
            return null;
        }

        return Carbon::parse($this->check_out_time)->setTimezone($this->overtimeDate->overtime->timezone)->format('Y-m-d H:i:s T');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function overtimeDate() {
        return $this->belongsTo(OvertimeDate::class, 'overtime_date_id');
    }

    public function employeeAttendance(): BelongsTo {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id');
    }
}
