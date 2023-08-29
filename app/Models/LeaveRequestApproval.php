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
 * @property-read Employee $employee
 */
class LeaveRequestApproval extends Model
{
    use HasFactory;

    const StatusPending = "pending";
    const StatusApproved = "approved";
    const StatusRejected = "rejected";

    public function getIsCanApproveAttribute() {
        /**
         * @var User $user
         */
        $user = request()->user();

        if (!$user) {
            return false;
        }

        /**
         * @var LeaveRequestApproval $leaveRequestApprovals
         */
        $leaveRequestApprovals = $this->leaveRequest->leaveRequestApprovals()->where('employee_id', '=', $user->employee_id)
            ->where('status', '=', LeaveRequestApproval::StatusPending)
            ->first();
        if (!$leaveRequestApprovals) {
            return false;
        }

        /**
         * @var LeaveRequestApproval $lastApproval
         */
        $lastApproval = $this->leaveRequest->leaveRequestApprovals()
            ->where('status', '=', LeaveRequestApproval::StatusPending)
            ->where('priority', '<', $leaveRequestApprovals->priority)
            ->exists();
        if($lastApproval) {
            return false;
        }

        return true;
    }

    public function getRealStatusAttribute() {
        if ($this->status == self::StatusPending) {
            /**
             * @var LeaveRequestApproval $lastApproval
             */
            $lastApproval = $this->leaveRequest->leaveRequestApprovals()
                ->where('status', '=', LeaveRequestApproval::StatusPending)
                ->where('priority', '<', $this->priority)
                ->exists();
            if($lastApproval) {
                return "Waiting Last Approval";
            }
        }

        return $this->status;
    }

    public function leaveRequest() {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
