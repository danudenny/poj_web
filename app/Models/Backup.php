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
 * @property string $request_type
 * @property string $timezone
 * @property string $file_url
 * @property int $source_unit_relation_id
 *
 * Relations:
 * @property-read Employee $requestorEmployee
 * @property-read Unit $unit
 * @property-read Unit $sourceUnit
 * @property-read Job $job
 * @property-read BackupHistory[] $backupHistory
 * @property-read BackupTime[] $backupTimes
 * @property-read BackupEmployee[] $backupEmployees
 * @property-read BackupApproval[] $backupApprovals
 */
class Backup extends Model
{
    use HasFactory;

    const TypeShift = "Shift";
    const TypeNonShift = "Non Shift";

    const StatusAssigned = "assigned";
    const StatusApproved = "approved";
    const StatusRejected = "rejected";

    const RequestTypeAssignment = "assignment";
    const RequestTypeRequest = "request";

    protected $table = 'backups';

    public function getIsCanApproveAttribute() {
        /**
         * @var User $user
         */
        $user = request()->user();

        if (!$user) {
            return false;
        }

        /**
         * @var BackupApproval $backupApproval
         */
        $backupApproval = $this->backupApprovals()->where('user_id', '=', $user->id)
            ->where('status', '=', BackupApproval::StatusPending)
            ->first();
        if (!$backupApproval) {
            return false;
        }

        /**
         * @var BackupApproval $lastApproval
         */
        $lastApproval = $this->backupApprovals()
            ->where('status', '=', BackupApproval::StatusPending)
            ->where('priority', '=', $backupApproval->priority - 1)
            ->exists();
        if($lastApproval) {
            return false;
        }

        return true;
    }

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

    public function backupApprovals(): HasMany {
        return $this->hasMany(BackupApproval::class, 'backup_id');
    }

    public function sourceUnit(): BelongsTo {
        return $this->belongsTo(Unit::class, 'source_unit_relation_id', 'relation_id');
    }
}
