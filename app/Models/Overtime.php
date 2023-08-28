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
 * @property int $unit_relation_id
 * @property int $job_id
 * @property string|null $last_status
 * @property string|null $last_status_at
 * @property string $start_date
 * @property string $end_date
 * @property string $timezone
 * @property string $notes
 * @property string|null $image_url
 * @property float $location_lat
 * @property float $location_long
 * @property string $request_type
 *
 * Relations:
 * @property-read Employee $requestorEmployee
 * @property-read Unit $unit
 * @property-read OvertimeHistory[] $overtimeHistories
 * @property-read OvertimeDate[] $overtimeDates
 * @property-read OvertimeApproval[] $overtimeApprovals
 */
class Overtime extends Model
{
    use HasFactory;

    const RequestTypeAssignment = "assignment";
    const RequestTypeRequest = "request";

    protected $hidden = [
        'start_datetime',
        'end_datetime'
    ];

    protected $appends = [
        'last_approver'
    ];

    protected $casts = [
        'created_at' => 'date:Y-m-d H:i:s'
    ];

    public function getIsCanApproveAttribute(): bool {
        /**
         * @var User $user
         */
        $user = request()->user();

        if (!$user) {
            return false;
        }

        /**
         * @var OvertimeApproval $overtimeApproval
         */
        $overtimeApproval = $this->overtimeApprovals()
            ->where('status', '=', OvertimeApproval::StatusPending)
            ->where('employee_id', '=', $user->employee_id)
            ->first();
        if (!$overtimeApproval) {
            return false;
        }

        $lastApproval = $this->overtimeApprovals()
            ->where('priority', '<', $overtimeApproval->priority)
            ->where('status', '=', OvertimeApproval::StatusPending)
            ->exists();
        if ($lastApproval) {
            return false;
        }

        return true;
    }

    public function getLastApproverAttribute() {
        $approver =  $this->overtimeApprovals()->get();

        if ($this->last_status != OvertimeHistory::TypePending && count($approver) == 0) {
            return [
                "name" => "Auto Approve",
            ];
        }

        /**
         * @var OvertimeApproval[] $items
         */
        $items = $approver->reverse();

        foreach ($items as $item) {
            if (($item->status == OvertimeApproval::StatusApproved) || ($item->status == OvertimeApproval::StatusRejected && ($item->notes != null || ($item == null && $item != "")))) {
                return [
                    "name" => $item->employee->name,
                ];
            }
        }

        return null;
    }

    /**
     * @return BelongsTo
     */
    public function requestorEmployee() {
        return $this->belongsTo(Employee::class, 'requestor_employee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function overtimeHistories() {
        return $this->hasMany(OvertimeHistory::class, 'overtime_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function overtimeDates() {
        return $this->hasMany(OvertimeDate::class, 'overtime_id');
    }

    /**
     * @return BelongsTo
     */
    public function unit() {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * @return HasMany
     */
    public function overtimeApprovals(): HasMany
    {
        return $this->hasMany(OvertimeApproval::class, 'overtime_id')->orderBy('priority', 'ASC');
    }
}
