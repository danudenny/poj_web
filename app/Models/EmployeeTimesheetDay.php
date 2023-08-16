<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static create(mixed $dayData)
 * @method static find(mixed $id)
 */
class EmployeeTimesheetDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'day',
        'start_time',
        'end_time',
        'employee_timesheet_id',
    ];

    public function employeeTimesheet(): BelongsTo
    {
        return $this->belongsTo(EmployeeTimesheet::class);
    }
}
