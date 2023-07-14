<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Attributes:
 * @property-read int $id
 * @property string $name
 * @property int $outlet_id
 * @property int $cabang_id
 * @property int $area_id
 * @property int $kanwil_id
 * @property int $corporate_id
 *
 * Relations:
 * @property-read User $user
 * @property-read Unit $outlet
 * @property-read Unit $cabang
 * @property-read Unit $area
 * @property-read Unit $kanwil
 * @property-read Unit $corporate
 */
class Employee extends Model
{
    use HasFactory;

    protected $appends = ['status'];


    public function getStatusAttribute()
    {
        if ($this->is_active) {
            return "Active";
        } else {
            return "In Active";
        }
    }

    /**
     * @return int|null
     */
    public function getLastUnitID(): int|null {
        if ($this->outlet_id) {
            return $this->outlet_id;
        } else if ($this->cabang_id) {
            return $this->cabang_id;
        } else if ($this->area_id) {
            return $this->area_id;
        } else if ($this->kanwil_id) {
            return $this->kanwil_id;
        } else if ($this->corporate_id) {
            return $this->corporate_id;
        }

        return null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeTimesheet(): HasOne
    {
        return $this->hasOne(EmployeeTimesheet::class);
    }

    public function employeeDetail(): HasOne
    {
        return $this->hasOne(EmployeeDetail::class);
    }

    public function user(): HasOne
    {
        return $this->HasOne(User::class);
    }

    public function job(): HasOne
    {
        return $this->hasOne(Job::class, 'odoo_job_id', 'job_id');
    }

    public function unit(): HasOne
    {
        return $this->hasOne(Unit::class, 'id', 'unit_id');
    }

    public function timesheetSchedules(): HasMany
    {
        return $this->hasMany(EmployeeTimesheetSchedule::class, 'employee_id');
    }

    public function kanwil(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'kanwil_id', 'relation_id')->where('unit_level', 4);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'area_id', 'relation_id')->where('unit_level', 5);
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'cabang_id', 'relation_id')->where('unit_level', 6);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'outlet_id', 'relation_id')->where('unit_level', 7);
    }

    public function lateCheckins(): HasOne
    {
        return $this->hasOne(LateCheckin::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class)->orderBy('id', 'desc');
    }
}
