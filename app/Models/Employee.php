<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Attributes:
 * @property-read int $id
 * @property int $odoo_employee_id
 * @property string $name
 * @property int $outlet_id
 * @property int $cabang_id
 * @property int $area_id
 * @property int $kanwil_id
 * @property int $corporate_id
 * @property int $default_operating_unit_id
 * @property string $unit_id
 * @property int $department_id
 * @property int $team_id
 * @property int $job_id
 * @property int $odoo_overtime_limit_id
 * @property int $odoo_working_hour_id
 *
 * Relations:
 * @property-read User $user
 * @property-read Unit $outlet
 * @property-read Unit $cabang
 * @property-read Unit $area
 * @property-read Unit $kanwil
 * @property-read Unit $corporate
 * @property-read Unit $unit
 * @property-read EmployeeAttendance[] $attendances
 * @property-read EmployeeTimesheetSchedule[] $timesheetSchedules
 * @property-read Unit $operatingUnit
 * @property-read Job $job
 * @property-read Department $department
 * @property-read Unit $defaultOperatingUnit
 * @property-read MasterOvertimeLimit $masterOvertimeLimit
 * @property-read WorkingHour $workingHour
 * @method static find($id)
 * @method static leftJoin(string $string, string $string1, string $string2, string $string3)
 * @method static chunk(int $chunkSize, \Closure $param)
 * @method static where(string $string, mixed $employeeId)
 */
class Employee extends Model
{
    use HasFactory;

    protected $appends = ['status', 'last_unit'];

    const UPDATED_AT = 'write_date';

    public function getStatusAttribute(): string
    {
        if ($this->is_active) {
            return "Active";
        } else {
            return "In Active";
        }
    }

    /**
     * @return Unit|null
     */
    public function getLastUnitAttribute(): ?Unit
    {
        return $this->getLastUnit();
    }

    public function getUnitOutletAttribute() {
        return $this->outlet;
    }

    public function getUnitCabangAttribute() {
        return $this->cabang;
    }

    public function getUnitAreaAttribute() {
        return $this->area;
    }

    public function getUnitKanwilAttribute() {
        return $this->kanwil;
    }

    public function getUnitCorporateAttribute() {
        return $this->corporate;
    }

    /**
     * @return string
     */
    public function getLastUnitID(): string {
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
        } else if ($this->default_operating_unit_id) {
            return $this->default_operating_unit_id;
        }

        return "";
    }

    /**
     * @return Unit|null
     */
    public function getLastUnit(): Unit|null {
        if ($this->outlet) {
            return $this->outlet;
        } else if ($this->cabang) {
            return $this->cabang;
        } else if ($this->area) {
            return $this->area;
        } else if ($this->kanwil) {
            return $this->kanwil;
        } else if ($this->corporate) {
            return $this->corporate;
        } else if ($this->operatingUnit) {
            return $this->operatingUnit;
        }

        return null;
    }

    /**
     * @return array
     */
    public function getAllUnitID(): array {
        $unitIDs = [];

        if ($this->default_operating_unit_id) {
            $unitIDs[] = $this->default_operating_unit_id;
        } else if ($this->outlet_id) {
            $unitIDs[] = $this->outlet_id;
        } else if ($this->cabang_id) {
            $unitIDs[] = $this->cabang_id;
        } else if ($this->area_id) {
            $unitIDs[] = $this->area_id;
        } else if ($this->kanwil_id) {
            $unitIDs[] = $this->kanwil_id;
        } else if ($this->corporate_id) {
            $unitIDs[] = $this->corporate_id;
        }

        return $unitIDs;
    }

    /**
     * @return Unit[]
     */
    public function getAllUnits(): array {
        $units = [];

        if ($this->operatingUnit) {
            $units[] = $this->operatingUnit;
        } else if ($this->outlet) {
            $units[] = $this->outlet;
        } else if ($this->cabang) {
            $units[] = $this->cabang;
        } else if ($this->area) {
            $units[] = $this->area;
        } else if ($this->kanwil) {
            $units[] = $this->kanwil;
        } else if ($this->corporate) {
            $units[] = $this->corporate;
        }

        return $units;
    }

    public function hasUnitID(int $unitID): bool {
        return (
            $this->default_operating_unit_id == $unitID || $this->outlet_id == $unitID || $this->cabang_id == $unitID || $this->area_id == $unitID || $this->kanwil_id == $unitID || $this->corporate_id == $unitID
        );
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
        return $this->HasOne(User::class, 'employee_id');
    }

    public function job(): HasOne
    {
        return $this->hasOne(Job::class, 'odoo_job_id', 'job_id');
    }

    public function unit(): HasOne
    {
        return $this->hasOne(Unit::class, 'relation_id', 'unit_id');
    }

    public function timesheetSchedules(): HasMany
    {
        return $this->hasMany(EmployeeTimesheetSchedule::class, 'employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'odoo_department_id');
    }

    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'corporate_id', 'relation_id')->where('unit_level', 3);
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

    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class, 'id', 'customer_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class)->orderBy('id', 'desc');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_unit_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_unit_id');
    }

    function getLastUnits($data) {
        $lastData = null;
        foreach ($data as $item) {
            if ($item === null) {
                break;
            }
            $lastData = $item;
        }
        return $lastData;
    }

    public function getRelatedUnit(): mixed
    {
        $workLocation = $this->last_unit;
        $parentRelationId = $workLocation->relation_id;

        $otherUnits = Unit::with(['children'])->where('parent_unit_id', $parentRelationId)
            ->where('id', '!=', $workLocation->id)
            ->get();

        return $otherUnits->toArray();
    }

    public function units(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'relation_id');
    }

    public function overtime(): HasMany
    {
        return $this->hasMany(OvertimeEmployee::class, 'employee_id');
    }

    public function backup(): HasMany
    {
        return $this->hasMany(BackupEmployee::class, 'employee_id')->with(['employee', 'backup']);
    }

    public function defaultOperatingUnit() {
        return $this->belongsTo(Unit::class, 'default_operating_unit_id', 'relation_id');
    }

    /**
     * @return BelongsTo
     */
    public function operatingUnit() {
        return $this->belongsTo(Unit::class, 'default_operating_unit_id', 'relation_id');
    }

    public function masterOvertimeLimit() {
        return $this->belongsTo(MasterOvertimeLimit::class, 'odoo_overtime_limit_id', 'odoo_overtime_limit_id');
    }

    public function workingHour() {
        return $this->belongsTo(WorkingHour::class, 'odoo_working_hour_id', 'odoo_working_hour_id');
    }
    public function getActiveNormalSchedule(string $timezone = 'UTC'): EmployeeTimesheetSchedule|null {
        /**
         * @var EmployeeTimesheetSchedule $employeeSchedule
         */
        $employeeSchedule = EmployeeTimesheetSchedule::query()
            ->where('employee_timesheet_schedules.employee_id', '=', $this->id)
            ->where(function(Builder $builder) use ($timezone) {
                $builder->orWhereRaw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '$timezone')::DATE = (CURRENT_TIMESTAMP at time zone '$timezone')::DATE")
                    ->orWhereRaw("(employee_timesheet_schedules.end_time::timestamp without time zone at time zone 'UTC' at time zone '$timezone')::DATE = (CURRENT_TIMESTAMP at time zone '$timezone')::DATE");
            })
            ->where(function(Builder $builder) {
                $builder->orWhereNull('employee_timesheet_schedules.check_in_time')
                    ->orWhereNull('employee_timesheet_schedules.check_out_time');
            })
            ->orderBy('employee_timesheet_schedules.start_time', 'ASC')
            ->first();

        return $employeeSchedule;
    }

    public function getActiveEvent(): EmployeeEvent|null {
        /**
         * @var EmployeeEvent|null $employeeEvent
         */
        $employeeEvent = EmployeeEvent::query()
            ->where('employee_events.employee_id', '=', $this->id)
            ->where('employee_events.is_need_absence', '=', true)
            ->whereRaw('employee_events.event_datetime::DATE = ?', [Carbon::now()->format('Y-m-d')])
            ->where(function(Builder $builder) {
                $builder->orWhereNull('employee_events.check_in_time')
                    ->orWhereNull('employee_events.check_out_time');
            })
            ->first();

        return $employeeEvent;
    }

    public function getActiveOvertime($timezone = 'UTC'): OvertimeEmployee|null {
        /**
         * @var OvertimeEmployee|null $overtime
         */
        $overtime = OvertimeEmployee::query()
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
            ->where('overtime_employees.employee_id', '=', $this->id)
            ->where('overtimes.last_status', '!=', OvertimeHistory::TypeRejected)
            ->where(function(Builder $builder) use ($timezone) {
                $builder->orWhereRaw("(overtime_dates.start_time::timestamp without time zone at time zone 'UTC' at time zone '$timezone')::DATE = (CURRENT_TIMESTAMP at time zone '$timezone')::DATE")
                    ->orWhereRaw("(overtime_dates.end_time::timestamp without time zone at time zone 'UTC' at time zone '$timezone')::DATE = (CURRENT_TIMESTAMP at time zone '$timezone')::DATE");
            })
            ->select(['overtime_employees.*'])
            ->where(function(Builder $builder) {
                $builder->orWhereNull('overtime_employees.check_in_time')
                    ->orWhereNull('overtime_employees.check_out_time');
            })
            ->orderBy('overtime_dates.start_time', 'ASC')
            ->first();

        return $overtime;
    }

    public function getActiveBackup($timezone = 'UTC'): BackupEmployeeTime|null {
        /**
         * @var BackupEmployeeTime|null $employeeBackup
         */
        $employeeBackup = BackupEmployeeTime::query()
            ->join('backup_times', 'backup_employee_times.backup_time_id', '=', 'backup_times.id')
            ->join('backups', 'backups.id', '=', 'backup_times.backup_id')
            ->where('status', '!=', Backup::StatusRejected)
            ->where('backup_employee_times.employee_id', '=', $this->id)
            ->where(function(Builder $builder) use ($timezone) {
                $builder->orWhereRaw("(backup_times.start_time::timestamp without time zone at time zone 'UTC' at time zone '$timezone')::DATE = (CURRENT_TIMESTAMP at time zone '$timezone')::DATE")
                    ->orWhereRaw("(backup_times.end_time::timestamp without time zone at time zone 'UTC' at time zone '$timezone')::DATE = (CURRENT_TIMESTAMP at time zone '$timezone')::DATE");
            })
            ->select(['backup_employee_times.*'])
            ->where(function(Builder $builder) {
                $builder->orWhereNull('backup_employee_times.check_in_time')
                    ->orWhereNull('backup_employee_times.check_out_time');
            })
            ->orderBy('backup_times.start_time', 'ASC')
            ->first();

        return $employeeBackup;
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
