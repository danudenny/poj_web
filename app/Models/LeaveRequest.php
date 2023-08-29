<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property string $last_status
 * @property string $start_date
 * @property string $end_date
 * @property string $updated_at
 *
 * Relations:
 * @property-read LeaveRequestApproval[] $leaveRequestApprovals
 * @property-read Employee $employee
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

    protected $appends = [
        'last_approver',
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

    public function getLastApproverAttribute() {
        $approver =  $this->leaveRequestApprovals()->get();

        if ($this->status != self::StatusOnProcess && count($approver) == 0) {
            return [
                "name" => "Auto Approve",
                "notes" => "",
                "updated_at" => Carbon::parse($this->updated_at, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s')
            ];
        }

        /**
         * @var LeaveRequestApproval[] $items
         */
        $items = $approver->reverse();

        foreach ($items as $item) {
            if (($item->status == LeaveRequestApproval::StatusApproved) || ($item->status == LeaveRequestApproval::StatusRejected && ($item->notes != null || ($item == null && $item != "")))) {
                return [
                    "name" => $item->employee->name,
                    "notes" => $item->notes,
                    "updated_at" => Carbon::parse($item->updated_at, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s')
                ];
            }
        }

        return null;
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
