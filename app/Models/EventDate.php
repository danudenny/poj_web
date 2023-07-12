<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $event_id
 * @property bool $is_need_absence
 * @property string $event_date
 * @property string $event_time
 */
class EventDate extends Model
{
    use HasFactory;
}
