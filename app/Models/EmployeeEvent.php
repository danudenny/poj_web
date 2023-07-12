<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $event_id
 * @property int $employee_id
 * @property bool $is_need_absence
 * @property string $event_date
 * @property string $event_time
 *
 * Relations:
 * @property-read Event $event
 * @property-read Employee $employee
 */
class EmployeeEvent extends Model
{
    use HasFactory;

    public function event() {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
