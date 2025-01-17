<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $priority
 * @property int $attendance_correction_request_id
 * @property int $employee_id
 * @property string $status
 * @property string $notes
 *
 * Relations:
 * @property-read AttendanceCorrectionRequest $attendanceCorrectionRequest
 */
class AttendanceCorrectionApproval extends Model
{
    use HasFactory;

    const StatusPending = "pending";
    const StatusApproved = "approved";
    const StatusRejected = "rejected";

    public function attendanceCorrectionRequest() {
        return $this->belongsTo(AttendanceCorrectionRequest::class, 'attendance_correction_request_id');
    }
}
