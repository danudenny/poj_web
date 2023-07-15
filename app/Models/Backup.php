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
 *
 * Relations:
 * @property-read Unit $unit
 * @property-read Job $job
 * @property-read EmployeeTimesheet $timesheet
 * @property-read Employee $assignee
 * @property-read BackupHistory[] $backupHistory
 */
class Backup extends Model
{
    use HasFactory;

    const TypeShift = "Shift";
    const TypeNonShift = "Non Shift";

    protected $table = 'backups';
    protected $fillable = [
        'unit_id',
        'job_id',
        'start_date',
        'end_date',
        'shift_type',
        'timesheet_id',
        'duration',
        'assignee_id',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(EmployeeTimesheet::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_id', 'id');
    }

    public function backupHistory(): HasMany
    {
        return $this->hasMany(BackupHistory::class);
    }

}
