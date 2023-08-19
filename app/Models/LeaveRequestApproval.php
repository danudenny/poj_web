<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $priority
 * @property int $leave_request_id
 * @property int $employee_id
 * @property string $status
 * @property string $notes
 *
 * Relations:
 * @property-read LeaveRequest $leaveRequest
 */
class LeaveRequestApproval extends Model
{
    use HasFactory;

    const StatusPending = "pending";
    const StatusApproved = "approved";
    const StatusRejected = "rejected";

    public function leaveRequest() {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }
}
