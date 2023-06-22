<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $table = 'employee_attendances';

    protected $fillable = [
        'employee_id',
        'real_check_in',
        'real_check_out',
        'ettendance_type',
        'lat',
        'long',
        'duration',
        'is_need_approval',
        'approved',
        'late_duration',
        'early_duration'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function employeeAttendanceHistory(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceHistory::class, 'employee_attendance_id', 'id');
    }
}
