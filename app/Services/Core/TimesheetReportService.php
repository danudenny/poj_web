<?php

namespace App\Services\Core;

use App\Http\Requests\TimesheetReport\CreateTimesheetReport;
use App\Models\Backup;
use App\Models\BackupEmployeeTime;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeTimesheetSchedule;
use App\Models\LeaveRequest;
use App\Models\MasterLeave;
use App\Models\OvertimeEmployee;
use App\Models\TimesheetReport;
use App\Models\TimesheetReportDetail;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TimesheetReportService extends BaseService
{
    public function index(Request $request) {
        $query = TimesheetReport::query();

        $query->orderBy('id', 'DESC');

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($query, $request)
        ]);
    }

    public function view(Request $request, int $id) {
        $timesheetReport = TimesheetReport::query()
            ->where('id', '=', $id)
            ->first();

        if (!$timesheetReport) {
            return response()->json([
                'status' => false,
                'message' => 'Timesheet Report not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $timesheetReport
        ]);
    }

    public function listTimesheetDetail(Request $request) {
        $query = TimesheetReportDetail::query()->with(['employee'])
            ->orderBy('id', 'ASC');

        if ($timesheetReportID = $request->get('timesheet_report_id')) {
            $query->where('timesheet_report_id', '=', $timesheetReportID);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($query, $request)
        ]);
    }

    public function sendTimesheetToERP(Request $request, int $id) {
        try {
            /**
             * @var TimesheetReport $timesheetReport
             */
            $timesheetReport = TimesheetReport::query()
                ->where('id', '=', $id)
                ->where('status', '=', TimesheetReport::StatusPending)
                ->first();

            if (!$timesheetReport) {
                return response()->json([
                    'status' => false,
                    'message' => 'Timesheet Report not found'
                ]);
            }

            DB::beginTransaction();

            foreach ($timesheetReport->timesheetReportDetails as $detail) {
                $payslipID = $this->getERPPayslip($detail->employee->odoo_employee_id);
                if ($payslipID) {
                    $this->setERPPayslip($payslipID, $detail->generateERPAttribute());

                    $detail->odoo_payslip_id = $payslipID;
                    $detail->save();
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getERPPayslip(int $odooEmployeeID) {
        $url = 'https://satria.optimajasa.co.id/api/v1/search/hr.payslip?limit=1&domain=["[\"employee_id\", \"=\", ' . $odooEmployeeID . ']","[\"state\", \"in\", [\"draft\", \"verify\"]]"]';

        $response = Http::withBasicAuth('admin', 'a')
            ->accept('application/json')
            ->withHeaders([
                'DATABASE' => 'DB_TRIAL',
            ])
            ->get($url);

        $result = $response->json();

        if (count($result) == 0) {
            return null;
        }

        return $result[0];
    }

    private function setERPPayslip(int $payslipID, array $attributes) {
        $values = urlencode(json_encode($attributes));
        $url = 'https://satria.optimajasa.co.id/api/v1/write/hr.payslip?ids=["' . $payslipID . '"]&values=' . $values;

        $response = Http::withBasicAuth('admin', 'a')
            ->accept('application/json')
            ->withHeaders([
                'DATABASE' => 'DB_TRIAL',
            ])
            ->put($url);

        $result = $response->json();
        if (count($result) == 0) {
            return false;
        }

        return true;
    }

    public function createTimesheetReport(CreateTimesheetReport $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var Unit $unit
             */
            $unit = Unit::query()
                ->where('relation_id', '=', $request->input('unit_relation_id'))
                ->first();
            if (!$unit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $timesheetIsExist = TimesheetReport::query()
                ->where('unit_relation_id', '=', $unit->relation_id)
                ->where(function (Builder $builder) use ($startDate, $endDate) {
                    $builder->orWhere(function (Builder $builder) use ($startDate) {
                        $builder->where('timesheet_reports.start_date', '<=', $startDate)
                            ->where('timesheet_reports.end_date', '>=', $startDate);
                    })->orWhere(function (Builder $builder) use ($endDate) {
                        $builder->where('timesheet_reports.start_date', '<=', $endDate)
                            ->where('timesheet_reports.end_date', '>=', $endDate);
                    });
                })->exists();
            if ($timesheetIsExist) {
                return response()->json([
                    'status' => false,
                    'message' => "Timesheet already generated on that range"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var Employee[] $employees
             */
            $employees = Employee::query()
                ->where('unit_id', '=', $unit->relation_id)
                ->get();
            if (count($employees) == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit don\'t have employee'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $timesheetReport = new TimesheetReport();
            $timesheetReport->start_date = $startDate;
            $timesheetReport->end_date = $endDate;
            $timesheetReport->unit_relation_id = $unit->relation_id;
            $timesheetReport->last_sync = Carbon::now();
            $timesheetReport->last_sync_by = $user->email;
            $timesheetReport->status = TimesheetReport::StatusPending;
            $timesheetReport->created_by = $user->email;
            $timesheetReport->save();

            foreach ($employees as $employee) {
                $data = $this->generateDataReport($employee, $startDate, $endDate);
                $data['timesheet_report_id'] = $timesheetReport->id;

                TimesheetReportDetail::query()->insert($data);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success',
            ]);

        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function syncTimesheetReport(Request $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var TimesheetReport $timesheetReport
             */
            $timesheetReport = TimesheetReport::query()
                ->where('id', '=', $id)
                ->where('status', '=', TimesheetReport::StatusPending)
                ->first();
            if (!$timesheetReport) {
                return response()->json([
                    'status' => false,
                    'message' => 'Timesheet report not found'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var Employee[] $employees
             */
            $employees = Employee::query()
                ->where('unit_id', '=', $timesheetReport->unit_relation_id)
                ->get();
            if (count($employees) == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit don\'t have employee'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $timesheetReport->last_sync = Carbon::now();
            $timesheetReport->last_sync_by = $user->email;
            $timesheetReport->save();

            foreach ($employees as $employee) {
                $data = $this->generateDataReport($employee, $timesheetReport->start_date, $timesheetReport->end_date);
                $data['timesheet_report_id'] = $timesheetReport->id;

                TimesheetReportDetail::query()
                    ->updateOrInsert([
                        'timesheet_report_id' => $timesheetReport->id,
                        'employee_id' => $employee->id
                    ],$data);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function generateDataReport(Employee $employee, string $startDate, string $endDate) {
        $dataEmployee = [
            'employee_id' => $employee->id,
            'total_work_day' => $this->totalWorkDay($employee, $startDate, $endDate),
            'attendance_days' => $this->totalAttendanceDay($employee, $startDate, $endDate),
            'total_work_weekdays' => $this->totalWorkWeekDays($employee, $startDate, $endDate),
            'total_work_national_holiday' => $this->totalWorkPublicHoliday($employee, $startDate, $endDate),
            'total_leave' => $this->totalLeaveRequest($employee, $startDate, $endDate),
            'total_absent' => $this->totalAbsent($employee, $startDate, $endDate),
            'total_backup' => $this->totalBackup($employee, $startDate, $endDate),
            'total_overtime_first' => 0,
            'total_overtime_second' => 0,
            'total_overtime_public_holiday_first' => 0,
            'total_overtime_public_holiday_second' => 0,
            'total_overtime_public_holiday_third' => 0,
            'total_late_15' => 0,
            'total_late_30' => 0,
            'total_late_45' => 0,
            'total_late_60' => 0,
            'total_late_75' => 0,
            'total_late_90' => 0,
            'total_late_105' => 0,
            'total_late_120' => 0,
            'early_check_out_15' => 0,
            'early_check_out_30' => 0,
            'early_check_out_45' => 0,
            'early_check_out_60' => 0,
            'early_check_out_75' => 0,
            'early_check_out_90' => 0,
            'early_check_out_105' => 0,
            'early_check_out_120' => 0,
        ];

        $totalOvertime = $this->totalOvertime($employee, $startDate, $endDate);
        $dataEmployee['total_overtime_first'] = $totalOvertime['total_overtime_first'];
        $dataEmployee['total_overtime_second'] = $totalOvertime['total_overtime_second'];

        $totalOvertime = $this->totalOvertimePublicHoliday($employee, $startDate, $endDate);
        $dataEmployee['total_overtime_public_holiday_first'] = $totalOvertime['total_overtime_first'];
        $dataEmployee['total_overtime_public_holiday_second'] = $totalOvertime['total_overtime_second'];
        $dataEmployee['total_overtime_public_holiday_third'] = $totalOvertime['total_overtime_third'];

        $totalLate = $this->totalLate($employee, $startDate, $endDate);
        foreach ($totalLate as $key => $value) {
            $dataEmployee[$key] = $value;
        }

        $totalEarlyCheckOut = $this->totalEarlyCheckOut($employee, $startDate, $endDate);
        foreach ($totalEarlyCheckOut as $key => $value) {
            $dataEmployee[$key] = $value;
        }

        return $dataEmployee;
    }

    private function totalWorkDay(Employee $employee, string $start_date, string $end_date) {
        return EmployeeTimesheetSchedule::query()
            ->where('employee_id', '=', $employee->id)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(employee_timesheet_schedules.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })->count();
    }

    private function totalAttendanceDay(Employee $employee, string $start_date, string $end_date) {
        return EmployeeTimesheetSchedule::query()
            ->where('employee_id', '=', $employee->id)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(employee_timesheet_schedules.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })
            ->whereNotNull('employee_timesheet_schedules.check_in_time')
            ->whereNotNull('employee_timesheet_schedules.check_out_time')
            ->count();
    }

    private function totalWorkWeekDays(Employee $employee, string $start_date, string $end_date) {
        return EmployeeTimesheetSchedule::query()
            ->leftJoin('public_holidays', 'public_holidays.holiday_date', '=', DB::raw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE"))
            ->where('employee_id', '=', $employee->id)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(employee_timesheet_schedules.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })
            ->whereNotNull('employee_timesheet_schedules.check_in_time')
            ->whereNotNull('employee_timesheet_schedules.check_out_time')
            ->whereNull('public_holidays.id')
            ->count();
    }

    private function totalWorkPublicHoliday(Employee $employee, string $start_date, string $end_date) {
        return EmployeeTimesheetSchedule::query()
            ->join('public_holidays', 'public_holidays.holiday_date', '=', DB::raw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE"))
            ->where('employee_id', '=', $employee->id)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(employee_timesheet_schedules.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })
            ->whereNotNull('employee_timesheet_schedules.check_in_time')
            ->whereNotNull('employee_timesheet_schedules.check_out_time')
            ->count();
    }

    private function totalLeaveRequest(Employee $employee, string $start_date, string $end_date) {
        return LeaveRequest::query()
            ->where('leave_requests.employee_id', '=', $employee->id)
            ->where('leave_requests.start_date', '>=', $start_date)
            ->where('leave_requests.end_date', '<=', $end_date)
            ->where('leave_requests.last_status', '=', LeaveRequest::StatusApproved)
            ->sum('leave_requests.days');
    }

    private function totalAbsent(Employee $employee, string $start_date, string $end_date) {
        $totalLeave = LeaveRequest::query()
            ->join('master_leaves', 'master_leaves.id', '=', 'leave_requests.leave_type_id')
            ->where('leave_requests.employee_id', '=', $employee->id)
            ->where('leave_requests.start_date', '>=', $start_date)
            ->where('leave_requests.end_date', '<=', $end_date)
            ->where('leave_requests.last_status', '=', LeaveRequest::StatusApproved)
            ->where('master_leaves.leave_code', '=', MasterLeave::CodeSickNonSKD)
            ->sum('leave_requests.days');

        $totalNotAttend = EmployeeTimesheetSchedule::query()
            ->where('employee_id', '=', $employee->id)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(employee_timesheet_schedules.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })
            ->where('employee_timesheet_schedules.end_time', '<', DB::raw('NOW()'))
            ->whereNull('employee_timesheet_schedules.check_in_time')
            ->whereNull('employee_timesheet_schedules.check_out_time')
            ->count();

        return $totalLeave + $totalNotAttend;
    }

    private function totalBackup(Employee $employee, string $start_date, string $end_date) {
        /**
         * @var BackupEmployeeTime[] $backupEmployees
         */
        $backupEmployees = BackupEmployeeTime::query()
            ->join('backup_times', 'backup_times.id', '=', 'backup_employee_times.backup_time_id')
            ->join('backups', 'backup_times.backup_id', '=', 'backups.id')
            ->where('backups.status', '!=', Backup::StatusRejected)
            ->whereNotNull('backup_employee_times.check_in_time')
            ->whereNotNull('backup_employee_times.check_out_time')
            ->where('backup_employee_times.employee_id', '=', $employee->id)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(backup_times.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(backup_times.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })
            ->select(['backup_employee_times.*'])
            ->get();

        $totalHours = 0;
        foreach ($backupEmployees as $backupEmployee) {
            $startTime = Carbon::parse($backupEmployee->backupTime->start_time);
            $endTime = Carbon::parse($backupEmployee->backupTime->end_time);

            $parsedTotal = $endTime->diff($startTime);

            $totalHours += $parsedTotal->h;
            if ($parsedTotal->i >= 45) {
                $totalHours += 1;
            }
        }

        return $totalHours;
    }

    private function totalOvertime(Employee $employee, string $start_date, string $end_date) {
        $result = [
            'total_overtime_first' => 0,
            'total_overtime_second' => 0
        ];

        /**
         * @var OvertimeEmployee[] $overtimeEmployees
         */
        $overtimeEmployees = OvertimeEmployee::query()
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
            ->leftJoin('public_holidays', 'public_holidays.holiday_date', '=', DB::raw("(overtime_dates.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE"))
            ->where('overtime_employees.employee_id', '=', $employee->id)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(overtime_dates.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(overtime_dates.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })
            ->whereNull('public_holidays.id')
            ->whereNotNull('overtime_employees.check_in_time')
            ->whereNotNull('overtime_employees.check_out_time')
            ->select(['overtime_employees.*'])
            ->groupBy(['overtime_employees.id'])
            ->get();

        foreach ($overtimeEmployees as $overtimeEmployee) {
            $paredTotalTime = Carbon::parse($overtimeEmployee->overtimeDate->total_overtime);
            $totalOvertime = $paredTotalTime->hour;

            if ($paredTotalTime->minute >= 45) {
                $totalOvertime += 1;
            }

            $first = 0;
            $second = 0;

            if ($totalOvertime > 0) {
                $first += 1;
                $totalOvertime -= 1;
            }

            if ($totalOvertime > 0) {
                $second += $totalOvertime;
                $totalOvertime = 0;
            }

            $result['total_overtime_first'] += $first;
            $result['total_overtime_second'] += $second;
        }

        return $result;
    }

    private function totalOvertimePublicHoliday(Employee $employee, string $start_date, string $end_date) {
        $result = [
            'total_overtime_first' => 0,
            'total_overtime_second' => 0,
            'total_overtime_third' => 0
        ];

        /**
         * @var OvertimeEmployee[] $overtimeEmployees
         */
        $overtimeEmployees = OvertimeEmployee::query()
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
            ->join('public_holidays', 'public_holidays.holiday_date', '=', DB::raw("(overtime_dates.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE"))
            ->where('overtime_employees.employee_id', '=', $employee->id)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(overtime_dates.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(overtime_dates.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })
            ->whereNotNull('overtime_employees.check_in_time')
            ->whereNotNull('overtime_employees.check_out_time')
            ->select(['overtime_employees.*'])
            ->groupBy(['overtime_employees.id'])
            ->get();

        foreach ($overtimeEmployees as $overtimeEmployee) {
            $paredTotalTime = Carbon::parse($overtimeEmployee->overtimeDate->total_overtime);
            $totalOvertime = $paredTotalTime->hour;

            if ($totalOvertime == 0 && $paredTotalTime->minute >= 45) {
                $totalOvertime += 1;
            }

            $firstHour = 8;
            $secondHour = 1;

            $first = 0;
            $second = 0;
            $third = 0;

            if ($totalOvertime > 0) {
                if ($totalOvertime <= $firstHour) {
                    $first += $totalOvertime;
                    $totalOvertime = 0;
                } else {
                    $first += $firstHour;
                    $totalOvertime -= $firstHour;
                }
            }

            if ($totalOvertime > 0) {
                if ($totalOvertime <= $secondHour) {
                    $second += $totalOvertime;
                    $totalOvertime = 0;
                } else {
                    $second += $secondHour;
                    $totalOvertime -= $secondHour;
                }
            }

            if ($totalOvertime > 0) {
                $third += $totalOvertime;
                $totalOvertime = 0;
            }

            $result['total_overtime_first'] += $first;
            $result['total_overtime_second'] += $second;
            $result['total_overtime_third'] += $third;
        }

        return $result;
    }

    private function totalLate(Employee $employee, string $start_date, string $end_date) {
        $result = [
            'total_late_15' => 0,
            'total_late_30' => 0,
            'total_late_45' => 0,
            'total_late_60' => 0,
            'total_late_75' => 0,
            'total_late_90' => 0,
            'total_late_105' => 0,
            'total_late_120' => 0
        ];

        /**
         * @var EmployeeAttendance[] $employeeAttendances
         */
        $employeeAttendances = EmployeeAttendance::query()
            ->join('employee_timesheet_schedules', 'employee_timesheet_schedules.employee_attendance_id', '=', 'employee_attendances.id')
            ->where('employee_attendances.employee_id', '=', $employee->id)
            ->where('employee_attendances.attendance_types', '=', EmployeeAttendance::AttendanceTypeNormal)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(employee_timesheet_schedules.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })
            ->whereNotNull('employee_attendances.real_check_in')
            ->whereNotNull('employee_attendances.real_check_out')
            ->select(['employee_attendances.*'])
            ->groupBy(['employee_attendances.id'])
            ->get();

        foreach ($employeeAttendances as $employeeAttendance) {
            $totalLate = $employeeAttendance->late_duration;

            if ($totalLate > 15 && $totalLate <= 30) {
                $result['total_late_15'] += 1;
            } else if ($totalLate > 30 && $totalLate <= 45) {
                $result['total_late_30'] += 1;
            } else if ($totalLate > 45 && $totalLate <= 60) {
                $result['total_late_45'] += 1;
            } else if ($totalLate > 60 && $totalLate <= 75) {
                $result['total_late_60'] += 1;
            } else if ($totalLate > 75 && $totalLate <= 90) {
                $result['total_late_75'] += 1;
            } else if ($totalLate > 90 && $totalLate <= 105) {
                $result['total_late_90'] += 1;
            } else if ($totalLate > 105 && $totalLate <= 120){
                $result['total_late_105'] += 1;
            } else if ($totalLate > 120) {
                $result['total_late_120'] += 1;
            }
        }

        return $result;
    }

    private function totalEarlyCheckOut(Employee $employee, string $start_date, string $end_date) {
        $result = [
            'early_check_out_15' => 0,
            'early_check_out_30' => 0,
            'early_check_out_45' => 0,
            'early_check_out_60' => 0,
            'early_check_out_75' => 0,
            'early_check_out_90' => 0,
            'early_check_out_105' => 0,
            'early_check_out_120' => 0
        ];

        /**
         * @var EmployeeAttendance[] $employeeAttendances
         */
        $employeeAttendances = EmployeeAttendance::query()
            ->join('employee_timesheet_schedules', 'employee_timesheet_schedules.employee_attendance_id', '=', 'employee_attendances.id')
            ->where('employee_attendances.employee_id', '=', $employee->id)
            ->where('employee_attendances.attendance_types', '=', EmployeeAttendance::AttendanceTypeNormal)
            ->where(function(Builder $builder) use ($start_date, $end_date) {
                $builder->orWhereRaw("(employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE >= '{$start_date}'")
                    ->orWhereRaw("(employee_timesheet_schedules.end_time::timestamp without time zone at time zone 'UTC' at time zone '{$this->getClientTimezone()}')::DATE <= '$end_date'");
            })
            ->whereNotNull('employee_attendances.real_check_in')
            ->whereNotNull('employee_attendances.real_check_out')
            ->select(['employee_attendances.*'])
            ->groupBy(['employee_attendances.id'])
            ->get();

        foreach ($employeeAttendances as $employeeAttendance) {
            $totalEarlyCheckOut = $employeeAttendance->early_check_out;

            if ($totalEarlyCheckOut > 15 && $totalEarlyCheckOut <= 30) {
                $result['early_check_out_15'] += 1;
            } else if ($totalEarlyCheckOut > 30 && $totalEarlyCheckOut <= 45) {
                $result['early_check_out_30'] += 1;
            } else if ($totalEarlyCheckOut > 45 && $totalEarlyCheckOut <= 60) {
                $result['early_check_out_45'] += 1;
            } else if ($totalEarlyCheckOut > 60 && $totalEarlyCheckOut <= 75) {
                $result['early_check_out_60'] += 1;
            } else if ($totalEarlyCheckOut > 75 && $totalEarlyCheckOut <= 90) {
                $result['early_check_out_75'] += 1;
            } else if ($totalEarlyCheckOut > 90 && $totalEarlyCheckOut <= 105) {
                $result['early_check_out_90'] += 1;
            } else if ($totalEarlyCheckOut > 105 && $totalEarlyCheckOut <= 120){
                $result['early_check_out_105'] += 1;
            } else if ($totalEarlyCheckOut > 120) {
                $result['early_check_out_120'] += 1;
            }
        }

        return $result;
    }
}
