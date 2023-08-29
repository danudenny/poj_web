<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $backup_time_id
 * @property int $employee_id
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
 * @property-read BackupTime $backupTime
 * @property-read Employee $employee
 * @property-read EmployeeAttendance $employeeAttendance
 */
class BackupEmployeeTime extends Model
{
    use HasFactory;

    protected $appends = [
        'check_in_time_with_unit_timezone',
        'check_out_time_with_unit_timezone',
        'check_in_time_with_employee_timezone',
        'check_out_time_with_employee_timezone',
        'check_in_time_with_timezone',
        'check_out_time_with_timezone'
    ];

    public function getCheckInTimeWithTimeZoneAttribute() {
        $time = $this->check_in_time;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getCheckOutTimeWithTimeZoneAttribute() {
        $time = $this->check_out_time;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getStartTimeWithTimeZoneAttribute() {
        $time = $this->backupTime->start_time;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getEndTimeWithTimeZoneAttribute() {
        $time = $this->backupTime->end_time;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s');
        }

        return null;
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
    public function getCheckInTimeWithUnitTimezoneAttribute(): string|null {
        if (is_null($this->check_in_time)) {
            return null;
        }

        return Carbon::parse($this->check_in_time)->setTimezone($this->backupTime->backup->timezone)->format('Y-m-d H:i:s T');
    }

    /**
     * @return string|null
     */
    public function getCheckOutTimeWithUnitTimezoneAttribute(): string|null {
        if (is_null($this->check_out_time)) {
            return null;
        }

        return Carbon::parse($this->check_out_time)->setTimezone($this->backupTime->backup->timezone)->format('Y-m-d H:i:s T');
    }

    public function backupTime(): BelongsTo {
        return $this->belongsTo(BackupTime::class, 'backup_time_id');
    }

    public function employee(): BelongsTo {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function employeeAttendance(): BelongsTo {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id');
    }
}
