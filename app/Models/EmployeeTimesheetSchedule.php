<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relations:
 * @property-read EmployeeTimesheet $timesheet
 */
class EmployeeTimesheetSchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_timesheet_schedules';

    protected $fillable = [
        'timesheet_id',
        'period_id',
        'employee_id',
        'date'
    ];

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(EmployeeTimesheet::class, 'timesheet_id');
    }

    public function period(): BelongsTo
    {
        $getMonth = Carbon::now()->month;
        return $this->belongsTo(Period::class, 'period_id')->where('month', $getMonth);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
