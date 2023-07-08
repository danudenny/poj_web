<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $incident_id
 * @property string $history_type
 * @property string $status
 * @property string $reason
 * @property string $created_at
 * @property string $updated_at
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
}
