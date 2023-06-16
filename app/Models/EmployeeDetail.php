<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmployeeDetail extends Model
{
    use HasFactory;

    protected $table = 'employee_details';
    protected $hidden = ['created_at', 'updated_at', 'employee_id', 'employee_timesheet_id'];

    protected $fillable = [
        'employee_id',
        'employee_timesheet_id',
        'work_arrangement',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function employeeTimesheet(): HasOne
    {
        return $this->hasOne(EmployeeTimesheet::class, 'id', 'employee_timesheet_id');
    }
}
