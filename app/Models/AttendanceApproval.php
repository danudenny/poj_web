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
 * @property-read Employee $employee
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

    protected $appends = [
        'real_status'
    ];

    public function getRealStatusAttribute() {
        if ($this->priority > 0) {
            $lastStatus = $this->employeeAttendance->attendanceApprovals()
                ->where('priority', '<', $this->priority)
                ->where('status', '=', self::StatusPending)
                ->exists();

            if ($lastStatus) {
                return "Waiting Last Approval";
            }
        }

        return $this->status;
    }

    public function employeeAttendance() {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
