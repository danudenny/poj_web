<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Relations:
 * @property-read Employee $employee
 */
class TimesheetReportDetail extends Model
{
    use HasFactory;

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
