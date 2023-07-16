<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $requestor_employee_id
 * @property string|null $approved_at
 * @property string $date_overtime
 * @property string $start_time
 * @property string $end_time
 * @property string $notes
 * @property string|null $image_url
 * @property float $location_lat
 * @property float $location_long
 *
 * Relations:
 * @property-read Employee $requestorEmployee
 * @property-read OvertimeHistory[] $overtimeHistories
 * @property-read OvertimeEmployee[] $overtimeEmployees
 */
class Overtime extends Model
{
    use HasFactory;

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
}
