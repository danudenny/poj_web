<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $priority
 * @property string $approval_type
 * @property int $employee_attendance_id
 * @property int $employee_id
 * @property string $status
 * @property string $notes
 *
 * Relations:
 * @property-read EmployeeAttendance $employeeAttendance
 */
class AttendanceApproval extends Model
{
    use HasFactory;

    const TypeOffsite = "offsite";
    const TypeOffline = "offline";

    const StatusPending = "pending";
    const StatusApproved = "approved";
    const StatusRejected = "rejected";

    protected $table = 'attendance_approvals';

    public function employeeAttendance() {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id');
    }
}
