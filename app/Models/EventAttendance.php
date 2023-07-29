<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $event_id
 * @property int $employee_id
 *
 * Attributes:
 * @property-read Employee $employee
 * @property-read Event $event
 */
class EventAttendance extends Model
{
    use HasFactory;

    public function employee(): BelongsTo {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function event(): BelongsTo {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
