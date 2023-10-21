<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property string $real_check_in
 * @property string $checkin_type
 * @property float $checkin_lat
 * @property float $checkin_long
 * @property bool $is_need_approval
 * @property string $attendance_types
 * @property float $checkin_real_radius
 * @property bool $approved
 * @property string $check_in_tz
 * @property bool $is_late
 * @property int $early_duration
 * @property int $late_duration
 * @property string $real_check_out
 * @property float $checkout_lat
 * @property float $checkout_long
 * @property float $checkout_real_radius
 * @property string $checkout_type
 * @property string $check_out_tz
 * @property string $notes
 * @property string $updated_at
 * @property int $early_check_out
 * @property string $check_in_image_url
 * @property string $check_out_image_url
 * @property string $check_in_notes
 * @property string $check_in_attachment_url
 *
 * Relations:
 * @property-read Employee $employee
 * @property-read EmployeeAttendanceHistory[] $employeeAttendanceHistory
 * @property-read AttendanceApproval[] $attendanceApprovals
 */
class EmployeeAttendance extends Model
{
    use HasFactory;

    const TypeOnSite = "onsite";
    const TypeOffSite = "offsite";

    const AttendanceTypeOvertime = "overtime";
    const AttendanceTypeBackup = "backup";
    const AttendanceTypeEvent = "event";
    const AttendanceTypeNormal = "normal";

    protected $table = 'employee_attendances';

    protected $fillable = [
        'employee_id',
        'real_check_in',
        'real_check_out',
        'ettendance_type',
        'lat',
        'long',
        'duration',
        'is_need_approval',
        'approved',
        'late_duration',
        'early_duration'
    ];

    protected $appends = [
        'check_in_time_with_client_timezone',
        'check_out_time_with_client_timezone',
        'last_approver',
        'unit_target'
    ];

    public function getFormattedStatusAttribute() {
        if ($this->is_need_approval) {
            return "Menunggu Persetujuan";
        } else {
            if ($this->approved) {
                return "Disetujui";
            } else {
                return "Ditolak";
            }
        }
    }

    public function getUnitTargetAttribute() {
        if ($this->attendance_types == self::AttendanceTypeOvertime) {
            /**
             * @var OvertimeEmployee $employeeOvertime
             */
            $employeeOvertime = OvertimeEmployee::query()->where('employee_attendance_id', '=', $this->id)->first();
            if ($employeeOvertime) {
                return $employeeOvertime->overtimeDate->overtime->unit;
            }

            return null;
        } else if ($this->attendance_types == self::AttendanceTypeNormal) {
            /**
             * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
             */
            $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()->where('employee_attendance_id', '=', $this->id)->first();
            if ($employeeTimesheetSchedule) {
                return $employeeTimesheetSchedule->unit;
            }

            return null;
        } else if ($this->attendance_types == self::AttendanceTypeBackup) {
            /**
             * @var BackupEmployeeTime $employeeBackup
             */
            $employeeBackup = BackupEmployeeTime::query()->where('employee_attendance_id', '=', $this->id)->first();
            if ($employeeBackup) {
                return $employeeBackup->backupTime->backup->unit;
            }

            return null;
        }
    }

    public function getCheckInTimeWithClientTimezoneAttribute() {
        $time = $this->real_check_in;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getCheckOutTimeWithClientTimezoneAttribute() {
        $time = $this->real_check_out;

        if ($time) {
            return Carbon::parse($time, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s');
        }

        return null;
    }

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
        $backupApproval = $this->attendanceApprovals()->where('employee_id', '=', $user->employee_id)
            ->where('status', '=', BackupApproval::StatusPending)
            ->first();
        if (!$backupApproval) {
            return false;
        }

        /**
         * @var BackupApproval $lastApproval
         */
        $lastApproval = $this->attendanceApprovals()
            ->where('status', '=', BackupApproval::StatusPending)
            ->where('priority', '<', $backupApproval->priority)
            ->exists();
        if($lastApproval) {
            return false;
        }

        return true;
    }

    public function getLastApproverAttribute() {
        $approver = $this->attendanceApprovals()->get();

        if ($this->approved && count($approver) == 0) {
            return [
                "name" => "Auto Approve",
                "notes" => "",
                "updated_at" => Carbon::parse($this->updated_at, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s')
            ];
        }

        /**
         * @var AttendanceApproval[] $items
         */
        $items = $approver->reverse();

        foreach ($items as $item) {
            if (($item->status == AttendanceApproval::StatusApproved) || ($item->status == AttendanceApproval::StatusRejected && ($item->notes != null || ($item == null && $item != "")))) {
                return [
                    "name" => $item->employee->name,
                    "notes" => $item->notes,
                    "updated_at" => Carbon::parse($item->updated_at, 'UTC')->setTimezone(getClientTimezone())->format('Y-m-d H:i:s')
                ];
            }
        }

        return null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function employeeAttendanceHistory(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceHistory::class, 'employee_attendances_id', 'id');
    }

    public function attendanceApprovals() {
        return $this->hasMany(AttendanceApproval::class, 'employee_attendance_id')->orderBy('priority', 'ASC');
    }
}
