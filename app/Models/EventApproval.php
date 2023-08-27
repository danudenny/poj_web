<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $priority
 * @property int $event_id
 * @property int $employee_id
 * @property string $status
 * @property string $notes
 *
 * Relations:
 * @property-read Event $event
 */
class EventApproval extends Model
{
    use HasFactory;

    const StatusPending = "pending";
    const StatusApproved = "approved";
    const StatusRejected = "rejected";

    protected $appends = [
        'real_status'
    ];

    public function getRealStatusAttribute() {
        if ($this->status == self::StatusPending) {
            /**
             * @var LeaveRequestApproval $lastApproval
             */
            $lastApproval = self::query()
                ->where('event_id', '=', $this->event_id)
                ->where('status', '=', LeaveRequestApproval::StatusPending)
                ->where('priority', '<', $this->priority)
                ->exists();
            if($lastApproval) {
                return "Waiting Last Approval";
            }
        }

        return $this->status;
    }

    public function event() {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
