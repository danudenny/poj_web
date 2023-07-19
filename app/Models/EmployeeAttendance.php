<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property string $real_check_in
 * @property string $checkin_type
 * @property float $checkin_lat
 * @property float $checkin_long
 * @property bool $is_need_approval
 * @property string $attendance_types
 * @property float $checkin_real_radius
 * @property bool $approved
 * @property string $check_in_tz
 * @property bool $is_late
 * @property int $late_duration
 * @property string $real_check_out
 * @property float $checkout_lat
 * @property float $checkout_long
 * @property float $checkout_real_radius
 * @property string $checkout_type
 * @property string $check_out_tz
 *
 * Relations:
 * @property-read Employee $employee
 * @property-read EmployeeAttendanceHistory[] $employeeAttendanceHistory
 */
class EmployeeAttendance extends Model
{
    use HasFactory;

    const TypeOnSite = "onsite";
    const TypeOffSite = "offsite";

    const AttendanceTypeOvertime = "overtime";
    const AttendanceTypeBackup = "backup";
    const AttendanceTypeEvent = "event";

    protected $table = 'employee_attendances';

    protected $fillable = [
        'employee_id',
        'real_check_in',
        'real_check_out',
        'ettendance_type',
        'lat',
        'long',
        'duration',
        'is_need_approval',
        'approved',
        'late_duration',
        'early_duration'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function employeeAttendanceHistory(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceHistory::class, 'employee_attendances_id', 'id');
    }
}
