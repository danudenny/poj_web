<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 *
 * Relations:
 * @property-read Employee $requestorEmployee
 * @property-read Unit $unit
 * @property-read OvertimeHistory[] $overtimeHistories
 * @property-read OvertimeDate[] $overtimeDates
 */
class Overtime extends Model
{
    use HasFactory;

    protected $hidden = [
        'start_datetime',
        'end_datetime'
    ];

    protected $appends = [
    ];

    protected $casts = [
        'created_at' => 'date:Y-m-d H:i:s'
    ];

    public function getIsCanApproveAttribute(): bool {
        /**
         * @var User $user
         */
        $user = request()->user();

        return $this->last_status === OvertimeHistory::TypePending && $user->inRoleLevel([Role::RoleAdmin, Role::RoleSuperAdministrator]);
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
}
