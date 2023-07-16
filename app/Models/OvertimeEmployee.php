<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property int $overtime_id
 * @property string|null $check_in_time
 * @property string|null $check_in_lat
 * @property string|null $check_in_long
 * @property string|null $check_out_time
 * @property string|null $check_out_lat
 * @property string|null $check_out_long
 *
 * Relations:
 * @property-read Employee $employee
 */
class OvertimeEmployee extends Model
{
    use HasFactory;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
