<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $requestor_employee_id
 * @property int $unit_relation_id
 * @property string|null $last_status
 * @property string|null $last_status_at
 * @property string $start_datetime
 * @property string $end_datetime
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
 * @property-read OvertimeEmployee[] $overtimeEmployees
 */
class Overtime extends Model
{
    use HasFactory;

    protected $hidden = [
        'start_datetime',
        'end_datetime'
    ];

    protected $appends = [
        'check_in_time',
        'check_out_time'
    ];

    protected $casts = [
        'created_at' => 'date:Y-m-d H:i:s'
    ];

    /**
     * @return string
     */
    public function getCheckInTimeAttribute(): string {
        return $this->getCheckInTime()->format('Y-m-d H:i:s T');
    }

    /**
     * @return string
     */
    public function getCheckOutTimeAttribute(): string {
        return $this->getCheckOutTime()->format('Y-m-d H:i:s T');
    }

    /**
     * @return Carbon
     */
    public function getCheckInTime(): Carbon {
        return Carbon::parse($this->start_datetime, 'UTC')->setTimezone($this->timezone);
    }

    /**
     * @return Carbon
     */
    public function getCheckOutTime(): Carbon {
        return Carbon::parse($this->end_datetime, 'UTC')->setTimezone($this->timezone);
    }

    public function getIsCanApproveAttribute(): bool {
        /**
         * @var User $user
         */
        $user = request()->user();

        return $this->last_status === OvertimeHistory::TypePending && $user->inRoleLevel([Role::RoleAdmin, Role::RoleSuperAdministrator]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
    public function overtimeEmployees() {
        return $this->hasMany(OvertimeEmployee::class, 'overtime_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function unit() {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }
}
