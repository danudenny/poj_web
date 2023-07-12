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
 * @property string $status
 * @property string|null $notes
 *
 * Relations:
 * @property-read Employee $employee
 */
class EventHistory extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at'  => 'date:Y-m-d H:i:s',
    ];

    public function employee(): BelongsTo {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
