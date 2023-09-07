<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property string $title
 * @property string $date
 * @property string $job_type
 * @property string $job_description
 * @property string $image
 * @property integer $employee_id
 * @property string $reference_type
 * @property string $reference_id
 * @property string $created_at
 */
class WorkReporting extends Model
{
    use HasFactory;

    const TypeNormal = "normal";
    const TypeOvertime = "overtime";
    const TypeBackup = "backup";

    protected $fillable = [
        'title',
        'date',
        'job_type',
        'job_description',
        'image',
        'employee_id',
    ];

    protected $appends = [
        'created_at_client_timezone',
        'target_unit'
    ];

    public function getTargetUnitAttribute() {
        switch ($this->reference_type) {
            case self::TypeOvertime:
                /**
                 * @var OvertimeEmployee $overtimeEmployee
                 */
                $overtimeEmployee = OvertimeEmployee::query()
                    ->where('id', '=', $this->reference_id)
                    ->first();
                if ($overtimeEmployee) {
                    return $overtimeEmployee->overtimeDate->overtime->unit;
                }
                break;
            case self::TypeBackup:
                /**
                 * @var BackupEmployeeTime $backupEmployeeTime
                 */
                $backupEmployeeTime = BackupEmployeeTime::query()
                    ->where('id', '=', $this->reference_id)
                    ->first();
                if ($backupEmployeeTime) {
                    return $backupEmployeeTime->backupTime->backup->unit;
                }
                break;
            case self::TypeNormal:
                /**
                 * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
                 */
                $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()
                    ->where('id', '=', $this->reference_id)
                    ->first();
                if ($employeeTimesheetSchedule) {
                    return $employeeTimesheetSchedule->unit;
                }
                break;
        }

        return null;
    }

    public function getCreatedAtClientTimezoneAttribute() {
        $time = Carbon::parse($this->created_at, 'UTC');
        $userLocation = getClientTimezone();

        if ($userLocation) {
            $time->setTimezone($userLocation);
        }

        return $time->format('Y-m-d\\TH:i:s.000000\\Z');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
