<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $overtime_id
 * @property int $employee_id
 * @property string $history_type
 * @property string|null $notes
 *
 * Relations:
 * @property-read Employee $employee
 */
class OvertimeHistory extends Model
{
    use HasFactory;

    const TypeSubmitted = "submitted";
    const TypeCheckIn = "check-in";
    const TypeCheckOut = "check-out";
    const TypeApproved = "approved";
    const TypeRejected = "rejected";

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
