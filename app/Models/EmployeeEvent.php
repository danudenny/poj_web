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
 * @property string $event_datetime
 * @property string $event_date
 * @property string $event_time
 * @property string|null $check_in_time
 * @property string|null $check_in_lat
 * @property string|null $check_in_long
 * @property string|null $check_out_time
 * @property string|null $check_out_lat
 * @property string|null $check_out_long
 * @property string|null $check_in_timezone
 * @property string|null $check_out_timezone
 *
 * Relations:
 * @property-read Event $event
 * @property-read Employee $employee
 */
class EmployeeEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'employee_id',
        'is_need_absence',
        'event_datetime',
        'created_at',
        'updated_at'
    ];

    public function event() {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
