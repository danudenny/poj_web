<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property int $employee_attendances_id
 * @property string $status
 */
class EmployeeAttendanceHistory extends Model
{
    use HasFactory;

    protected $table = 'employee_attendance_histories';

    protected $fillable = [
        'employee_id',
        'employee_attendances_id',
        'status'
    ];

    //status is enum
    //'pending','reviewed','approved','completed','rejected','corrected'

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendances_id', 'id');
    }

}
