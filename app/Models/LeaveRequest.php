<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property string $last_status
 *
 * Relations:
 * @property-read LeaveRequestApproval[] $leaveRequestApprovals
 */
class LeaveRequest extends Model
{
    use HasFactory;

    const StatusOnProcess = 'on process';
    const StatusRejected = 'rejected';
    const StatusApproved = 'approved';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'last_status',
        'file_url'
    ];

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
        $leaveRequestApprovals = $this->leaveRequestApprovals()->where('employee_id', '=', $user->employee_id)
            ->where('status', '=', LeaveRequestApproval::StatusPending)
            ->first();
        if (!$leaveRequestApprovals) {
            return false;
        }

        /**
         * @var LeaveRequestApproval $lastApproval
         */
        $lastApproval = $this->leaveRequestApprovals()
            ->where('status', '=', LeaveRequestApproval::StatusPending)
            ->where('priority', '<', $leaveRequestApprovals->priority)
            ->exists();
        if($lastApproval) {
            return false;
        }

        return true;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(MasterLeave::class);
    }

    public function leaveHistory(): HasMany
    {
        return $this->hasMany(LeaveRequestHistory::class);
    }

    public function leaveRequestApprovals(): HasMany
    {
        return $this->hasMany(LeaveRequestApproval::class, 'leave_request_id');
    }
}
