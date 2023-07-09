<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $incident_id
 * @property string $history_type
 * @property string $status
 * @property string $reason
 * @property int $employee_id
 * @property string $incident_analysis
 * @property string $follow_up_incident
 * @property string $created_at
 * @property string $updated_at
 *
 * Relations:
 * @property-read Employee $employee
 */
class IncidentHistory extends Model
{
    use HasFactory;

    const TypeSubmit = "submit";
    const TypeFollowUp = "follow-up";
    const TypeClosure = "closure";

    const StatusSubmitted = "submitted";
    const StatusReject = "reject";
    const StatusApprove = "approve";
    const StatusClose = "close";
    const StatusDisclose = "disclose";

    protected $casts = [
        'created_at'  => 'date:Y-m-d H:i:s',
    ];

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
