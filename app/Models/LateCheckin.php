<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateCheckin extends Model
{
    use HasFactory;

    protected $table = 'late_checkin_reports';
    protected $fillable = [
        'employee_id',
        'late_15',
        'late_30',
        'late_45',
        'late_60',
        'late_75',
        'late_90',
        'late_105',
        'late_120',
        'total_late_15',
        'total_late_30',
        'total_late_45',
        'total_late_60',
        'total_late_75',
        'total_late_90',
        'total_late_105',
        'total_late_120',
        'month',
        'year'
    ];
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
