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
 *
 * Relations:
 * @property-read EmployeeTimesheet $timesheet
 * @property-read EmployeeAttendance|null $employeeAttendance
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

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(EmployeeTimesheet::class, 'timesheet_id');
    }

    public function period(): BelongsTo
    {
        $getMonth = Carbon::now()->month;
        return $this->belongsTo(Period::class, 'period_id')->where('month', $getMonth);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function employeeAttendance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id');
    }
}
