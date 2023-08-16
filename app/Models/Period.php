<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property string $year
 * @property string $month
 * @method static where(string $string, $period_id)
 */
class Period extends Model
{
    use HasFactory;

    protected $table = 'periods';
    protected $fillable = [
        'year',
        'month'
    ];

    public function timesheetSchedules(): HasMany
    {
        return $this->hasMany(EmployeeTimesheetSchedule::class, 'period_id');
    }
}
