<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property int $requestor_employee_id
 * @property string $unit_id
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
 * @property string $updated_at
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

    protected $appends = [
        'last_approver'
    ];

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
        $backupApproval = $this->backupApprovals()->where('employee_id', '=', $user->employee_id)
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
            ->where('priority', '<', $backupApproval->priority)
            ->exists();
        if($lastApproval) {
            return false;
        }

        return true;
    }

    public function getLastApproverAttribute() {
        $approver =  $this->backupApprovals()->get();

        if ($this->status != self::StatusAssigned && count($approver) == 0) {
            return [
                "name" => "Auto Approve",
                "notes" => "",
                "updated_at" => Carbon::parse($this->updated_at, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s')
            ];
        }

        /**
         * @var BackupApproval[] $items
         */
        $items = $approver->reverse();

        foreach ($items as $item) {
            if (($item->status == BackupApproval::StatusApproved) || ($item->status == BackupApproval::StatusRejected && ($item->notes != null || ($item == null && $item != "")))) {
                return [
                    "name" => $item->employee->name,
                    "notes" => $item->notes,
                    "updated_at" => Carbon::parse($item->updated_at, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s')
                ];
            }
        }

        return null;
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
        return $this->hasMany(BackupApproval::class, 'backup_id')->orderBy('priority', 'ASC');
    }

    public function sourceUnit(): BelongsTo {
        return $this->belongsTo(Unit::class, 'source_unit_relation_id', 'relation_id');
    }
}
