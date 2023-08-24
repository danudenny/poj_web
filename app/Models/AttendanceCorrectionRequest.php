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

    public function attendanceCorrectionApprovals() {
        return $this->hasMany(AttendanceCorrectionApproval::class, 'attendance_correction_request_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
