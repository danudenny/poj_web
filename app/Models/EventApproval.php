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

    public function event() {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
