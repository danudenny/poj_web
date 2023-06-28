<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Backup extends Model
{
    use HasFactory;

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
