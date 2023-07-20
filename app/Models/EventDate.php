<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $event_id
 * @property bool $is_need_absence
 * @property string $event_datetime
 * @property string $event_date
 * @property string $event_time
 *
 * Relations:
 * @property-read Event $event
 */
class EventDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'is_need_absence',
        'event_datetime',
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'date_time_with_timezone'
    ];

    /**
     * @return string|null
     */
    public function getDateTimeWithTimezoneAttribute(): string|null {
        return Carbon::parse($this->event_datetime)->setTimezone($this->event->timezone)->format('Y-m-d H:i:s T');
    }

    public function event(): BelongsTo {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
