<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Models\BackupEmployeeTime;
use App\Models\Employee;
use App\Models\EmployeeTimesheet;
use App\Models\EmployeeTimesheetSchedule;
use App\Models\OvertimeEmployee;
use App\Models\OvertimeHistory;
use App\Models\Period;
use App\Models\PublicHoliday;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class SyncNonShiftSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:non-shift-schedule {startDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Non Shift Schedule';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $argStartDate = $this->argument('startDate');
            if (!$argStartDate) {
                $argStartDate = Carbon::now()->setTimezone(getClientTimezone())->format('Y-m-d');
            }

            $outerCheck = Carbon::now();
            $startDate = Carbon::parse($argStartDate);
            $endDate = $startDate->copy()->endOfMonth();
            $yearMonth = $startDate->format('Y-m');

            $holidayDates = [];
            $holidayDatesObjs = PublicHoliday::query()->whereRaw("TO_CHAR(holiday_date, 'YYYY-mm') = '$yearMonth'")->get();

            foreach ($holidayDatesObjs as $holidayDatesObj) {
                $holidayDates[] = $holidayDatesObj->holiday_date;
            }

            DB::beginTransaction();

            /**
             * @var Period $period
             */
            $period = Period::query()
                ->where('year', '=', $startDate->year)
                ->where('month', '=', $startDate->month)
                ->first();
            $totalInsertedEmployee = 0;
            $employeeTimesheet = [];
            $dataUnits = [];
            $insertArr = [];

            Employee::query()
                ->join('working_hours', 'working_hours.odoo_working_hour_id', '=', 'employees.odoo_working_hour_id')
                ->where('working_hours.name', '=', 'NON SHIFT')
                ->select(['employees.*'])
                ->chunk(1000, function(Collection $employees) use ($startDate, $endDate, &$totalInsertedEmployee, $period, &$employeeTimesheet, &$dataUnits, &$insertArr, $holidayDates) {
                    /**
                     * @var Employee $employee
                     */
                    foreach ($employees as $employee) {
                        $units = [$employee->unit_id, $employee->corporate_id];
                        $unitsJoin = join('.', $units);

                        if (!isset($employeeTimesheet[$unitsJoin])) {
                            $employeeTimesheet[join('.', $units)] = DB::query()->from("employee_timesheet_days")->select([
                                "employee_timesheet_days.id",
                                "employee_timesheet_days.employee_timesheet_id",
                                "employee_timesheet_days.start_time",
                                "employee_timesheet_days.end_time",
                                "employee_timesheet_days.day"
                            ])
                                ->join('employee_timesheet', 'employee_timesheet_days.employee_timesheet_id', '=', 'employee_timesheet.id')
                                ->join('units', 'units.id', '=', DB::raw("CAST(employee_timesheet.unit_id AS BIGINT)"))
                                ->whereIn('units.relation_id', $units)
                                ->orderByRaw('units.unit_level, employee_timesheet.id DESC')
                                ->get()->toArray();
                        }

                        if (!$employeeTimesheet[$unitsJoin]) {
                            $this->info('❌️ '. $employee->name);
                            continue;
                        }

                        if (!isset($dataUnits[$employee->unit_id])) {
                            $localUnit = $employee->unit;

                            if (!$localUnit || (!$localUnit->lat || !$localUnit->long)) {
                                $dataUnits[$employee->unit_id] = null;
                            } else {
                                $timezone = getTimezoneV2($localUnit->lat, $localUnit->long);
                                if ($timezone != "") {
                                    $localUnit->timezone = $timezone;
                                    $dataUnits[$employee->unit_id] = $localUnit;
                                } else {
                                    $dataUnits[$employee->unit_id] = null;
                                }
                            }
                        }

                        $unit = $dataUnits[$employee->unit_id];
                        if (!$unit) {
                            $this->info('❌️ '. $employee->name);
                            continue;
                        }

                        $totalInsertedEmployee += 1;

                        $startCheck = Carbon::now();

                        $timesheetDays = [];
                        foreach ($employeeTimesheet[$unitsJoin] as $item) {
                            $timesheetDays[$item->day] = $item;
                        }

                        $employeeUnavailableDate = [];
                        $employeeFilledDays = DB::query()->from('employee_timesheet_schedules')
                            ->select([
                                'employee_timesheet_schedules.employee_id',
                                DB::raw("CONCAT(periods.year, '-', periods.month, '-', employee_timesheet_schedules.date)::DATE AS date")
                            ])
                            ->join('periods', 'employee_timesheet_schedules.period_id', '=', 'periods.id')
                            ->where('employee_timesheet_schedules.employee_id', '=', $employee->id)
                            ->whereRaw("CONCAT(periods.year, '-', periods.month, '-', employee_timesheet_schedules.date)::DATE >= '{$startDate->format('Y-m-d')}'")
                            ->whereRaw("CONCAT(periods.year, '-', periods.month, '-', employee_timesheet_schedules.date)::DATE <= '{$endDate->format('Y-m-d')}'")
                            ->get();
                        foreach ($employeeFilledDays as $employeeFilledDay) {
                            $employeeUnavailableDate[] = $employeeFilledDay->date;
                        }

                        $total = [
                            'success' => 0,
                            'failed' => 0
                        ];

                        for($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                            if (!isset($timesheetDays[$date->dayName])) {
                                continue;
                            }
                            if(in_array($date->format('Y-m-d'), $holidayDates)) {
                                continue;
                            }

                            $timesheet = $timesheetDays[$date->dayName];

                            $now = Carbon::now();
                            $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $timesheet->start_time . ':00', $unit->timezone)->setTimezone('UTC');
                            $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $timesheet->end_time . ':00', $unit->timezone)->setTimezone('UTC');

                            if ($startTime->lessThan($now)) {
                                continue;
                            }

                            if ($endTime->lessThan($startTime)) {
                                $endTime->addDay();
                            }

                            if (in_array($date->format('Y-m-d'), $employeeUnavailableDate)) {
                                $total['failed'] += 1;
                                continue;
                            }

                            $param = [
                                'employee_id' => $employee->id,
                                'timesheet_id' => $timesheet->employee_timesheet_id,
                                'period_id' => $period->id,
                                'date' => $date->format('d'),
                                'start_time' => $startTime->format('Y-m-d H:i:s'),
                                'end_time' => $endTime->format('Y-m-d H:i:s'),
                                'early_buffer' => $unit->early_buffer ?? 0,
                                'late_buffer' => $unit->late_buffer ?? 0,
                                'timezone' => $unit->timezone,
                                'latitude' => $unit->lat,
                                'longitude' => $unit->long,
                                'unit_relation_id' => $unit->relation_id,
                                'start_time_timesheet' => $timesheet->start_time,
                                'end_time_timesheet' => $timesheet->end_time,
                            ];

                            $insertArr[] = $param;
                            $total['success'] += 1;
                        }

                        $totalCheckInSeconds = Carbon::now()->diffInMilliseconds($startCheck);
                        $this->info("✅️ $employee->name [Success: {$total['success']} , Failed: {$total['failed']}]. (Time to Process {$totalCheckInSeconds} ms)");
                    }
                });

            if (count($insertArr) > 0) {
                DB::table('employee_timesheet_schedules')->insert($insertArr);
            }
            DB::commit();

            $outerTime = Carbon::now()->diffInSeconds($outerCheck);
            $memmUsage = $this->convert(memory_get_usage(true));
            $this->info("Finish with total time {$outerTime}s {$totalInsertedEmployee} employee and memm_usage {$memmUsage}");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}
