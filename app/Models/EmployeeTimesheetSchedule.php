<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property int $timesheet_id
 * @property int $period_id
 * @property string|int $date
 * @property string $start_time
 * @property string $end_time
 * @property string $early_buffer
 * @property string $late_buffer
 * @property string $timezone
 * @property float $latitude
 * @property float $longitude
 * @property string $check_in_time
 * @property string $check_out_time
 * @property string $check_in_latitude
 * @property string $check_in_longitude
 * @property string $check_in_timezone
 * @property string $check_out_latitude
 * @property string $check_out_longitude
 * @property string $check_out_timezone
 * @property int $employee_attendance_id
 * @property string $unit_relation_id
 *
 * Relations:
 * @property-read EmployeeTimesheet $timesheet
 * @property-read EmployeeAttendance|null $employeeAttendance
 * @property-read Employee $employee
 * @property-read Unit $unit
 * @property-read Period $period
 * @method static where(string $string, mixed $employeeId)
 */
class EmployeeTimesheetSchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_timesheet_schedules';

    protected $fillable = [
        'timesheet_id',
        'period_id',
        'employee_id',
        'date'
    ];

    protected $appends = [
        'real_date'
    ];

    public function getCheckInTimeWithTimeZoneAttribute() {
        $time = $this->check_in_time;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone($this->check_in_timezone)->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getCheckOutTimeWithTimeZoneAttribute() {
        $time = $this->check_out_time;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone($this->check_out_timezone)->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getStartTimeWithTimeZoneAttribute() {
        $time = $this->start_time;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone($this->timezone)->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getEndTimeWithTimeZoneAttribute() {
        $time = $this->end_time;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone($this->timezone)->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getRealDateAttribute() {
        return Carbon::parse($this->start_time, 'UTC')->setTimezone($this->timezone)->format('Y-m-d');
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(EmployeeTimesheet::class, 'timesheet_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function employeeAttendance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id');
    }

    public function unit() {
        return  $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }
}
