<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property int $requestor_employee_id
 * @property int $unit_id
 * @property int $job_id
 * @property string $start_date
 * @property string $end_date
 * @property string $shift_type
 * @property float $duration
 * @property string $status
 * @property float $location_lat
 * @property float $location_long
 * @property string $timezone
 * @property string $file_url
 *
 * Relations:
 * @property-read Employee $requestorEmployee
 * @property-read Unit $unit
 * @property-read Job $job
 * @property-read BackupHistory[] $backupHistory
 * @property-read BackupTime[] $backupTimes
 * @property-read BackupEmployee[] $backupEmployees
 */
class Backup extends Model
{
    use HasFactory;

    const TypeShift = "Shift";
    const TypeNonShift = "Non Shift";

    const StatusAssigned = "assigned";
    const StatusApproved = "approved";
    const StatusRejected = "rejected";

    protected $table = 'backups';

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'relation_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id', 'odoo_job_id');
    }

    public function backupHistory(): HasMany
    {
        return $this->hasMany(BackupHistory::class);
    }

    public function backupTimes(): HasMany
    {
        return $this->hasMany(BackupTime::class, 'backup_id');
    }

    public function backupEmployees(): HasMany
    {
        return $this->hasMany(BackupEmployee::class, 'backup_id');
    }

    public function requestorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requestor_employee_id');
    }
}
