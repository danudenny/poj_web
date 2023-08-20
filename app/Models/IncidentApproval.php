<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $priority
 * @property int $incident_id
 * @property int $employee_id
 * @property string $status
 * @property string $notes
 *
 * Relations:
 * @property-read Incident $incident
 */
class IncidentApproval extends Model
{
    use HasFactory;

    const StatusPending = "pending";
    const StatusReject = "reject";
    const StatusApprove = "approve";
    const StatusClose = "close";
    const StatusDisclose = "disclose";

    public function incident() {
        return $this->belongsTo(Incident::class, 'incident_id');
    }
}
