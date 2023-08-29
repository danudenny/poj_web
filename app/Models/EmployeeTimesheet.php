<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property int $unit_id
 * @property string $shift_type
 * @property string $start_time
 * @property string $end_time
 * @property string $name
 *
 * Relations:
 * @property-read Unit $unit
 * @method static create($timeshiftData)
 * @method static find($id)
 * @method static where(string $string, $timesheet_id)
 */
class EmployeeTimesheet extends Model
{
    use HasFactory;

    public const TypeShift = "shift";
    public const TypeNonShift = "non_shift";

    protected $table = 'employee_timesheet';
    protected $hidden = ['created_at', 'updated_at'];
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_active',
        'unit_id',
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

    public function timesheetDays(): HasMany
    {
        return $this->hasMany(EmployeeTimesheetDay::class, 'employee_timesheet_id');
    }
}
