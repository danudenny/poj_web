<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\BackupEmployeeTime;
use App\Models\Employee;
use App\Models\EmployeeTimesheetSchedule;
use App\Models\OvertimeEmployee;
use App\Models\OvertimeHistory;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ScheduleService extends BaseService
{
    protected function isEmployeeActiveScheduleExist(array $employeeIDs, string $startTime, string $endTime): bool {
        $isExistNormal = EmployeeTimesheetSchedule::query()
            ->whereIn('employee_timesheet_schedules.employee_id', $employeeIDs)
            ->where(function(Builder $builder) use ($startTime, $endTime) {
                $builder->orWhere(function(Builder $builder) use ($startTime) {
                    $builder->where( 'employee_timesheet_schedules.start_time', '<=', $startTime)
                        ->where('employee_timesheet_schedules.end_time', '>=', $startTime);
                })->orWhere(function(Builder $builder) use ($endTime) {
                    $builder->where('employee_timesheet_schedules.start_time', '<=', $endTime)
                        ->where('employee_timesheet_schedules.end_time', '>=', $endTime);
                });
            })
            ->exists();
        if ($isExistNormal) {
            return true;
        }

        $isExistOvertime = OvertimeEmployee::query()
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
            ->whereIn('overtime_employees.employee_id', $employeeIDs)
            ->where('overtimes.last_status', '!=', OvertimeHistory::TypeRejected)
            ->where(function(Builder $builder) use ($startTime, $endTime) {
                $builder->orWhere(function(Builder $builder) use ($startTime) {
                    $builder->where( 'overtime_dates.start_time', '<=', $startTime)
                        ->where('overtime_dates.end_time', '>=', $startTime);
                })->orWhere(function(Builder $builder) use ($endTime) {
                    $builder->where('overtime_dates.start_time', '<=', $endTime)
                        ->where('overtime_dates.end_time', '>=', $endTime);
                });
            })
            ->exists();
        if ($isExistOvertime) {
            return true;
        }

        $isExistBackup = BackupEmployeeTime::query()
            ->join('backup_times', 'backup_employee_times.backup_time_id', '=', 'backup_times.id')
            ->join('backups', 'backups.id', '=', 'backup_times.backup_id')
            ->where('status', '!=', Backup::StatusRejected)
            ->whereIn('backup_employee_times.employee_id', $employeeIDs)
            ->where(function(Builder $builder) use ($startTime, $endTime) {
                $builder->orWhere(function(Builder $builder) use ($startTime) {
                    $builder->where( 'backup_times.start_time', '<=', $startTime)
                        ->where('backup_times.end_time', '>=', $startTime);
                })->orWhere(function(Builder $builder) use ($endTime) {
                    $builder->where('backup_times.start_time', '<=', $endTime)
                        ->where('backup_times.end_time', '>=', $endTime);
                });
            })
            ->exists();
        if ($isExistBackup) {
            return true;
        }

        return false;
    }
}
