<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $priority
 * @property int $overtime_id
 * @property int $employee_id
 * @property string $status
 * @property string $notes
 *
 * Relations:
 * @property-read Overtime $overtime
 */
class OvertimeApproval extends Model
{
    use HasFactory;

    const StatusPending = "pending";
    const StatusApproved = "approved";
    const StatusRejected = "rejected";

    protected $appends = ['real_status'];

    public function getRealStatusAttribute() {
        if ($this->priority > 0) {
            $lastStatus = OvertimeApproval::query()
                ->where('overtime_id', '=', $this->overtime_id)
                ->where('priority', '<', $this->priority)
                ->where('status', '=', self::StatusPending)
                ->exists();

            if ($lastStatus) {
                return "Waiting Last Approval";
            }
        }

        return $this->status;
    }

    /**
     * @return BelongsTo
     */
    public function overtime(): BelongsTo {
        return $this->belongsTo(Overtime::class, 'overtime_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
