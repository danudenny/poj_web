<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property string $start_time
 * @property string $end_time
 *
 * Relations:
 * @property-read Unit $unit
 */
class EmployeeTimesheet extends Model
{
    use HasFactory;

    protected $table = 'employee_timesheet';
    protected $hidden = ['created_at', 'updated_at'];
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_active',
        'unit_id',
        'days',
        'shift_type',
    ];

    protected $casts = [
        'days' => 'array',
    ];

    public function employeeDetails(): HasMany
    {
        return $this->hasMany(EmployeeDetail::class);
    }

    public function employee(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function timesheetSchedules(): HasMany
    {
        return $this->hasMany(EmployeeTimesheetSchedule::class, 'timesheet_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
