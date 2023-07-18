<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'unit_id'
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
}
