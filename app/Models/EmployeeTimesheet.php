<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    public function employeeDetails()
    {
        return $this->hasMany(EmployeeDetail::class);
    }

    public function employee()
    {
        return $this->hasMany(Employee::class);
    }
}
