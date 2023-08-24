<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property int $employee_attendance_id
 * @property int $reference_type
 * @property int $reference_id
 * @property int $status
 * @property int $check_in_time
 * @property int $check_out_time
 * @property string $notes
 * @property string $file_url
 *
 * Relations:
 * @property-read AttendanceCorrectionApproval[] $attendanceCorrectionApprovals
 * @property-read Employee $employee
 */
class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    public function getIsCanApproveAttribute() {
        /**
         * @var User $user
         */
        $user = request()->user();

        if (!$user) {
            return false;
        }

        /**
         * @var AttendanceCorrectionApproval $userApproval
         */
        $userApproval = $this->attendanceCorrectionApprovals()->where('employee_id', '=', $user->employee_id)
            ->where('status', '=', AttendanceCorrectionApproval::StatusPending)
            ->first();
        if (!$userApproval) {
            return false;
        }

        /**
         * @var AttendanceCorrectionApproval $lastApproval
         */
        $lastApproval = $this->attendanceCorrectionApprovals()
            ->where('status', '=', AttendanceCorrectionApproval::StatusPending)
            ->where('priority', '<', $userApproval->priority)
            ->exists();
        if($lastApproval) {
            return false;
        }

        return true;
    }

    public function attendanceCorrectionApprovals() {
        return $this->hasMany(AttendanceCorrectionApproval::class, 'attendance_correction_request_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
