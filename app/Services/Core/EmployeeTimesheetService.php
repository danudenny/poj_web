<?php

namespace App\Services\Core;

use App\Http\Requests\EmployeeTimesheet\UpdateEmployeeTimesheetScheduleRequest;
use App\Models\Employee;
use App\Models\EmployeeTimesheet;
use App\Models\EmployeeTimesheetDay;
use App\Models\EmployeeTimesheetSchedule;
use App\Models\Period;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use App\Services\ScheduleService;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EmployeeTimesheetService extends ScheduleService {
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    public function index($request, $id): JsonResponse
    {
        try {
            $data = EmployeeTimesheet::query()->with('timesheetDays');
            $data->when(request()->has('name'), function ($query) {
                $query->where('name', 'like', '%' . request()->get('name') . '%');
            });
            $data->when(request()->has('start_time'), function ($query) {
                $query->where('start_time', 'like', '%' . request()->get('start_time') . '%');
            });
            $data->when(request()->has('end_time'), function ($query) {
                $query->where('end_time', 'like', '%' . request()->get('end_time') . '%');
            });
            $data->when(request()->has('sort'), function ($query) {
                $query->orderBy(request()->get('sort'), request()->get('order'));
            });
            $data->when(request()->has('shift_type'), function ($query) {
                $query->where('shift_type', '=', request()->get('shift_type'));
            });
            $data->where('unit_id', $id);
            $data->orderBy('id');

            return response()->json([
                'status' => 'success',
                'message' => 'Data fetched successfully',
                'data' => $data->paginate(request()->get('limit') ?? 10)
            ], 201);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG . ' : ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function save($request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {

            $timeshift = EmployeeTimesheet::create([
                'unit_id' => $id,
                'name' => $request->name,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'shift_type' => $request->shift_type,
            ]);

            if ($request->input('shift_type') === 'non_shift') {
                foreach ($request->input('days') as $dayData) {
                    $dayData['employee_timesheet_id'] = $timeshift->id;
                    EmployeeTimesheetDay::create($dayData);
                }
            }

            if (!$timeshift->save()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save data',
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data saved successfully',
                'data' => $timeshift
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

    }

    public function view(Request $request, int $id) {
        /**
         * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
         */
        $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()->with(['employee', 'unit', 'timesheet'])
            ->where('id', '=', $id)
            ->first();
        if (!$employeeTimesheetSchedule) {
            return response()->json([
                'status' => false,
                'message' => 'Employee timesheet schedule not found!'
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $employeeTimesheetSchedule
        ]);
    }

    /**
     * @throws Exception
     */
    public function show($unit_id, $id): JsonResponse
    {
        try {
            $timesheet = EmployeeTimesheet::with('timesheetDays')
                ->where('unit_id', $unit_id)
                ->where('id', $id)
                ->first();

            if (!$timesheet) {
                throw new Exception('Data not found');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data fetched successfully',
                'data' => $timesheet
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    /**
     * @throws Exception
     */
    public function update($request, $id): JsonResponse
    {
        $dataExists = EmployeeTimesheet::where('id', $id)->first();
        if (!$dataExists) {
            throw new Exception('Data not found');
        }

        DB::beginTransaction();
        try {
            $timeshift = EmployeeTimesheet::find($id);
            $timeshift->name = $request->name;
            $timeshift->start_time = $request->start_time;
            $timeshift->end_time = $request->end_time;
            $timeshift->shift_type = $request->shift_type;

            if ($request->input('shift_type') === 'non_shift') {
                foreach ($request->input('days') as $dayData) {
                    $dayData['employee_timesheet_id'] = $timeshift->id;
                    if (isset($dayData['id'])) {
                        $day = EmployeeTimesheetDay::find($dayData['id']);
                        $day->day = $dayData['day'];
                        $day->start_time = $dayData['start_time'];
                        $day->end_time = $dayData['end_time'];
                        $day->save();
                    } else {
                        EmployeeTimesheetDay::create($dayData);
                    }
                }
            }

            if (!$timeshift->save()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save data',
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully',
                'data' => $timeshift
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function delete($id): JsonResponse
    {
        $dataExists = EmployeeTimesheet::where('id', $id)->first();
        if (!$dataExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }

        $employeeTimesheetSchedule = EmployeeTimesheetSchedule::where('timesheet_id', $id)->first();
        if ($employeeTimesheetSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timesheet is assigned to employee'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $data = EmployeeTimesheet::find($id);
            if (!$data->delete()) {
                throw new Exception('Failed to delete data');
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data deleted successfully',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function assignTimesheetSchedule($request): JsonResponse
    {
        $employeeIds = $request->employee_ids;

        /**
         * @var EmployeeTimesheet $timesheetExists
         */
        $timesheetExists = EmployeeTimesheet::where('id', $request->timesheet_id)->first();
        if (!$timesheetExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timesheet id not found',
                'data' => ''
            ], 404);
        }

        $unit = $timesheetExists->unit;
        if ($unit->lat == null || $unit->long == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit don\'t have latitude and longitude',
                'data' => ''
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        $timezone = getTimezoneV2($unit->lat, $unit->long);

        /**
         * @var Period $periodsExists
         */
        $periodsExists = Period::where('id', $request->period_id)->first();
        if (!$periodsExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Period id not found',
                'data' => ''
            ], 404);
        }


        $parsedStartTime = '';
        $parsedEndTime = '';
        if ($timesheetExists->shift_type == 'shift') {
            $parsedStartTime = Carbon::parse(sprintf("%s-%s-%s %s:00", $periodsExists->year, $periodsExists->month, $request->date, $timesheetExists->start_time), $timezone);
            $parsedEndTime = Carbon::parse(sprintf("%s-%s-%s %s:00", $periodsExists->year, $periodsExists->month, $request->date, $timesheetExists->end_time), $timezone);
            if ($parsedEndTime->lessThan($parsedStartTime)) {
                $parsedEndTime->addDays();
            }
        }

        $schedule = null;

        DB::beginTransaction();
        try {
            foreach ($employeeIds as $employeeId) {
                $employeeExists = Employee::where('id', $employeeId)->get();
                if (!$employeeExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Employee id not found',
                        'data' => ''
                    ], 404);
                }

                $existingSchedule = EmployeeTimesheetSchedule::where('employee_id', $employeeId)
                    ->where('date', $request->date)
                    ->first();

                if ($existingSchedule) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Employee is already assigned for the selected date',
                    ], 409);
                }

                if ($timesheetExists->shift_type == 'shift') {
                    $parsedUTCStartTime = $parsedStartTime->setTimezone('UTC');
                    $parsedUTCEndTime = $parsedEndTime->setTimezone('UTC');

                    $isExistActiveSchedule = $this->isEmployeeActiveScheduleExist([$employeeId], $parsedUTCStartTime->format('Y-m-d H:i:s'), $parsedUTCEndTime->format('Y-m-d H:i:s'));
                    if ($isExistActiveSchedule) {
                        /**
                         * @var Employee $employee
                         */
                        $employee = Employee::query()->where('id', '=', $employeeId)->first();
                        throw new Exception(sprintf("%s has active schedule", $employee->name));
                    }

                    $schedule = new EmployeeTimesheetSchedule();
                    $schedule->employee_id = $employeeId;
                    $schedule->timesheet_id = $request->timesheet_id;
                    $schedule->period_id = $request->period_id;
                    $schedule->date = $request->date;
                    $schedule->start_time = $parsedUTCStartTime;
                    $schedule->end_time = $parsedUTCEndTime;
                    $schedule->early_buffer = $unit->early_buffer ?? 0;
                    $schedule->late_buffer = $unit->late_buffer ?? 0;
                    $schedule->timezone = $timezone;
                    $schedule->latitude = $unit->lat;
                    $schedule->longitude = $unit->long;
                    $schedule->unit_relation_id = $unit->relation_id;
                } else {
                    $year = Carbon::now($timezone)->year;
                    $month = Carbon::now($timezone)->month;
                    $requestedDay = $request->date;

                    $startDate = Carbon::create($year, $month, $requestedDay, 0, 0, 0, $timezone);
                    $lastDayOfMonth = Carbon::parse($startDate)->endOfMonth();

                    foreach ($timesheetExists->timesheetDays as $timesheet) {
                        $dayIndex = array_search($timesheet->day, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);

                        if ($dayIndex !== false) {
                            $dayOffset = ($dayIndex + 7 - $startDate->dayOfWeek) % 7;
                            $currentDate = $startDate->copy()->addDays($dayOffset);
                            while ($currentDate <= $lastDayOfMonth) {
                                $parsedStartTime = Carbon::parse(sprintf("%s-%s-%s %s:00", $currentDate->year, $currentDate->month, $currentDate->day, $timesheet->start_time), $timezone)->setTimezone('UTC');
                                $parsedEndTime = Carbon::parse(sprintf("%s-%s-%s %s:00", $currentDate->year, $currentDate->month, $currentDate->day, $timesheet->end_time), $timezone)->setTimezone('UTC');
                                if ($parsedEndTime->isBefore($parsedStartTime)) {
                                    $parsedEndTime->addDays(1);
                                }

                                $isExistActiveSchedule = $this->isEmployeeActiveScheduleExist([$employeeId], $parsedStartTime->format('Y-m-d H:i:s'), $parsedEndTime->format('Y-m-d H:i:s'));
                                if ($isExistActiveSchedule) {
                                    /**
                                     * @var Employee $employee
                                     */
                                    $employee = Employee::query()->where('id', '=', $employeeId)->first();
                                    throw new Exception(sprintf("%s has active schedule", $employee->name));
                                }

                                $schedule = new EmployeeTimesheetSchedule();
                                $schedule->employee_id = $employeeId;
                                $schedule->timesheet_id = $request->timesheet_id;
                                $schedule->period_id = $request->period_id;
                                $schedule->date = $currentDate->format('d');
                                $schedule->start_time = $parsedStartTime;
                                $schedule->end_time = $parsedEndTime;
                                $schedule->early_buffer = $unit->early_buffer ?? 0;
                                $schedule->late_buffer = $unit->late_buffer ?? 0;
                                $schedule->timezone = $timezone;
                                $schedule->latitude = $unit->lat;
                                $schedule->longitude = $unit->long;
                                $schedule->unit_relation_id = $unit->relation_id;

                                $schedule->save();
                                $currentDate->addWeek();
                            }
                        }
                    }
                }


                if (!$schedule->save()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to save data',
                        'data' => $schedule
                    ], 500);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully',
                'data' => $schedule
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => ''
            ], 500);
        }
    }

    public function reassignOrUpdateTimesheetSchedule($request): JsonResponse
    {
        $employeeIds = $request->employee_ids;

        $timesheetExists = EmployeeTimesheet::where('id', $request->timesheet_id)->first();
        if (!$timesheetExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timesheet id not found',
                'data' => ''
            ], 404);
        }
        $unit = $timesheetExists->unit;

        $timezone = getTimezoneV2($unit->lat, $unit->long);

        $periodsExists = Period::where('id', $request->period_id)->first();
        if (!$periodsExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Period id not found',
                'data' => ''
            ], 404);
        }

        $parsedStartTime = Carbon::parse(sprintf("%s-%s-%s %s:00", $periodsExists->year, $periodsExists->month, $request->date, $timesheetExists->start_time), $timezone);
        $parsedEndTime = Carbon::parse(sprintf("%s-%s-%s %s:00", $periodsExists->year, $periodsExists->month, $request->date, $timesheetExists->end_time), $timezone);
        if ($parsedEndTime->lessThan($parsedStartTime)) {
            $parsedEndTime->addDays(1);
        }

        $existingSchedules = EmployeeTimesheetSchedule::where('timesheet_id', $request->timesheet_id)
            ->where('period_id', $request->period_id)
            ->where('date', $request->date)
            ->get();

        DB::beginTransaction();
        try {
            foreach ($employeeIds as $employeeId) {
                $existingSchedule = $existingSchedules->where('employee_id', $employeeId)->first();

                if ($existingSchedule) {
                    $existingSchedule->start_time = $parsedStartTime->setTimezone('UTC');
                    $existingSchedule->end_time = $parsedEndTime->setTimezone('UTC');
                    $existingSchedule->early_buffer = $unit->early_buffer ?? 0;
                    $existingSchedule->late_buffer = $unit->late_buffer ?? 0;
                    $existingSchedule->timezone = $timezone;
                    $existingSchedule->latitude = $unit->lat;
                    $existingSchedule->longitude = $unit->long;

                    if (!$existingSchedule->save()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Failed to update data',
                            'data' => $existingSchedule
                        ], 500);
                    }
                } else {
                    $schedule = new EmployeeTimesheetSchedule();
                    $schedule->employee_id = $employeeId;
                    $schedule->timesheet_id = $request->timesheet_id;
                    $schedule->period_id = $request->period_id;
                    $schedule->date = $request->date;
                    $schedule->start_time = $parsedStartTime->setTimezone('UTC');
                    $schedule->end_time = $parsedEndTime->setTimezone('UTC');
                    $schedule->early_buffer = $unit->early_buffer ?? 0;
                    $schedule->late_buffer = $unit->late_buffer ?? 0;
                    $schedule->timezone = $timezone;
                    $schedule->latitude = $unit->lat;
                    $schedule->longitude = $unit->long;

                    if (!$schedule->save()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Failed to save data',
                            'data' => $schedule
                        ], 500);
                    }
                }

            }

            foreach ($existingSchedules as $existingSchedule) {
                if (!in_array($existingSchedule->employee_id, $employeeIds)) {
                    $existingSchedule->delete();
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully',
                'data' => ""
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => ''
            ], 500);
        }
    }


    public function getEmployeeSchedule($request): JsonResponse
    {
        $roles = Auth::user();
        $groupedData = [];
        $getMonth = Carbon::now()->format('m');
        $schedule = EmployeeTimesheetSchedule::query();
        $schedule->with(['employee', 'employee.corporate', 'employee.kanwil', 'employee.area', 'employee.cabang','employee.outlet','timesheet', 'timesheet.unit', 'period']);
        $schedule->when($request->query('date'), function ($query) use ($request) {
            $query->where('date', $request->query('date'));
        });
        $schedule->when($request->query('month'), function ($query) use ($request) {
            $query->whereHas('period', function ($query) use ($request) {
                $query->where('month', $request->query('month'));
            });
        });
        $schedule->when($request->query('year'), function ($query) use ($request) {
            $query->whereHas('period', function ($query) use ($request) {
                $query->where('year', $request->query('year'));
            });
        });
        if ($roles->hasRole('superadmin')) {
            $schedule = $schedule
                ->orderBy('date')
                ->orderBy('period_id')
                ->get();
            $groupedData = $schedule->groupBy(function ($item) {
                if ($item->period) {
                    $year = $item->period->year;
                    $month = $item->period->month;
                    $getDate = Carbon::parse($year . '-' . $month);
                    return $item->date . '-' . $getDate->format('F Y');
                } else {
                    return null;
                }
            });
        } else if ($roles->hasRole('admin')) {
            $schedule = $schedule->where('corporate_id', request()->query('unit_id'));
            $schedule = $schedule->where('kanwil_id', request()->query('unit_id'));
            $schedule = $schedule->orWhere('area_id', request()->query('unit_id'));
            $schedule = $schedule->orWhere('cabang_id', request()->query('unit_id'));
            $schedule = $schedule->orWhere('outlet_id', request()->query('unit_id'));
            $schedule = $schedule->paginate(31);
        } else if ($roles->hasRole('staff')) {
            $schedule = $schedule->where('employee_id', $roles->employee_id);
            $schedule = $schedule->paginate(31);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this data',
                'data' => ''
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data fetched successfully',
            'data' => $schedule
        ], 200);
    }

    public function showEmployeeSchedule($request): JsonResponse
    {
        $schedule = EmployeeTimesheetSchedule::with(['employee', 'timesheet', 'period'])
            ->when($request->date, function ($query) use ($request) {
                $query->where('date', $request->date);
            })
            ->when($request->employee_id, function ($query) use ($request) {
                $query->whereHas('employee', function ($query) use ($request) {
                    $query->where('id', $request->employee_id);
                })->first();
            })
            ->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Data fetched successfully',
            'data' => $schedule
        ], 200);
    }

    public function scheduleById($request): JsonResponse
    {
        $schedule = EmployeeTimesheetSchedule::with(['timesheet', 'period'])
            ->when($request->date, function ($query) use ($request) {
                $query->where('date', $request->date);
            })
            ->when($request->month, function ($query) use ($request) {
                $query->whereHas('period', function ($query) use ($request) {
                    $query->where('month', $request->month);
                });
            })
            ->when($request->employee_id, function ($query) use ($request) {
                $query->whereHas('employee', function ($query) use ($request) {
                    $query->where('id', $request->employee_id);
                });
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data fetched successfully',
            'data' => $schedule
        ], 200);
    }

    public function updateSchedule($request): JsonResponse
    {
        $employeeIds = $request->employee_ids;
        $schedule = EmployeeTimesheetSchedule::where('date', $request->date)->first();
        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schedule id not found',
                'data' => ''
            ], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($employeeIds as $employeeId) {
                $employeeExists = Employee::where('id', $employeeId)->get();
                if (!$employeeExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Employee id not found',
                        'data' => ''
                    ], 404);
                }

                $schedule->employee_id = $employeeId;
                $schedule->timesheet_id = $request->timesheet_id;
                $schedule->period_id = $request->period_id;
                $schedule->date = $request->date;

                if (!$schedule->save()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to save data',
                        'data' => ''
                    ], 500);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully',
                'data' => ''
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => ''
            ], 500);
        }
    }

    public function deleteSchedule($request): JsonResponse
    {
        $schedule = EmployeeTimesheetSchedule::where('date', $request->date)->first();
        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schedule date not found',
                'data' => ''
            ], 404);
        }

        DB::beginTransaction();
        try {
            if (!$schedule->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete data',
                    'data' => ''
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data deleted successfully',
                'data' => ''
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => ''
            ], 500);
        }
    }

    public function indexSchedule(Request $request): JsonResponse
    {
        $query = EmployeeTimesheetSchedule::with([
                'employee',
                'timesheet',
                'period',
                'timesheet.timesheetDays'
            ])
            ->groupBy('employees.id', 'employee_timesheet_schedules.id', 'employee_timesheet.id')
            ->join('employees', 'employees.id', '=', 'employee_timesheet_schedules.employee_id')
            ->join('employee_timesheet', 'employee_timesheet.id', '=', 'employee_timesheet_schedules.timesheet_id');

        if ($unitRelationID = $request->query('unit_relation_id')) {
            $query->where('employees.unit_id', '=', $unitRelationID);
        }
        if ($monthlyYear = $request->query('monthly_year')) {
            $query->whereRaw("TO_CHAR(employee_timesheet_schedules.start_time::DATE, 'YYYY-mm')::TEXT = '${monthlyYear}'");
        }
        if ($shiftType = $request->query('shift_type')) {
            $query->where('employee_timesheet.shift_type', '=', $shiftType);
        }
        if ($employeeName = $request->query('employee_name')) {
            $query->whereRaw("employees.name ILIKE '%{$employeeName}%'");
        }
        if ($employeeJobID = $request->query('employee_job_id')) {
            $query->where('employees.job_id', '=', $employeeJobID);
        }

        /**
         * @var EmployeeTimesheetSchedule[] $timesheetAssignments
         */
        $timesheetAssignments = $query->get();

        $daysOfMonth = range(1, now()->daysInMonth);

        $transformedData = [];
        $rowNumber = 1;

        foreach ($timesheetAssignments as $assignment) {
            $employeeName = $assignment->employee->name;
            $date = $assignment->date;

            if (!isset($transformedData[$employeeName])) {
                $transformedData[$employeeName] = [
                    'no' => $rowNumber,
                    'employee_name' => $employeeName,
                    'unit' => $assignment->employee->last_unit->name,
                    'job' => $assignment->employee->job->name,
                ];

                foreach ($daysOfMonth as $day) {
                    $transformedData[$employeeName][$day] = null;
                }
            }

            $dailyEntries = $transformedData[$employeeName];

            if ($assignment->timesheet->shift_type === 'shift') {
                $shiftEntry = $assignment->timesheet->start_time . '-' . $assignment->timesheet->end_time;
                $dailyEntries[$date] = [
                    'time' => $shiftEntry,
                    'id' => $assignment->id,
                    'check_in_time' => $assignment->check_in_time,
                    'check_out_time' => $assignment->check_out_time,
                    'unit' => $assignment->unit->name
                ];
            } else {
                if ($date >= 1 && $date <= count($daysOfMonth)) {
                    $timesheetDay = null;
                    foreach ($assignment->timesheet->timesheetDays as $day) {
                        if ($day->day === now()->setDay($date)->englishDayOfWeek) {
                            $timesheetDay = $day;
                            break;
                        }
                    }
                    if ($timesheetDay) {
                        $shiftEntry = $timesheetDay->start_time . '-' . $timesheetDay->end_time;
                        $dailyEntries[$date] = [
                            'time' => $shiftEntry,
                            'id' => $assignment->id,
                            'check_in_time' => $assignment->check_in_time,
                            'check_out_time' => $assignment->check_out_time,
                            'unit' => $assignment->unit->name
                        ];
                    }
                }

            }


            $totalHours = $assignment->timesheet->shift_type === 'shift'
                ? $this->calculateTotalHours($assignment->timesheet->start_time, $assignment->timesheet->end_time)
                : $this->calculateTotalHoursFromDays($assignment->timesheet->timesheetDays);

            $transformedData[$employeeName] = $dailyEntries;
            $transformedData[$employeeName]['total_hours'] = isset($transformedData[$employeeName]['total_hours'])
                ? $transformedData[$employeeName]['total_hours'] + $totalHours
                : $totalHours;

            $rowNumber++;
        }

        $transformedData = array_values($transformedData);

        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $dayAbbreviations = [];
        $daysOfMonth = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayAbbreviations[] = $date->locale('id')->dayName;
            $daysOfMonth[] = $date->day;
        }

        $header = array_merge(['No', 'Employee Name', 'Unit', 'Job Title'], $daysOfMonth, ['Total Hours']);

        return response()->json([
            'status' => 'success',
            'message' => 'Data fetched successfully',
            'data' => $transformedData,
            'header' => $header,
            'header_abbrv' => $dayAbbreviations,
        ]);
    }

    private function calculateTotalHours($startTime, $endTime): float
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        $totalMinutes = $end->diffInMinutes($start);
        return floor($totalMinutes / 60);
    }

    private function calculateTotalHoursFromDays($days): float
    {
        $totalMinutes = 0;

        foreach ($days as $day) {
            $start = Carbon::parse($day->start_time);
            $end = Carbon::parse($day->end_time);
            $totalMinutes += $end->diffInMinutes($start);
        }

        return floor($totalMinutes / 60);
    }

    public function deleteEmployeeTimesheetSchedule(Request $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
             */
            $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()
                ->where('id', '=', $id)
                ->first();
            if (!$employeeTimesheetSchedule) {
                return response()->json([
                    'status' => false,
                    'message' => "employee timesheet schedule not found"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($employeeTimesheetSchedule->check_in_time != null || $employeeTimesheetSchedule->check_out_time) {
                return response()->json([
                    'status' => false,
                    'message' => "employee timesheet schedule not found"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $employeeTimesheetSchedule->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Success!"
            ]);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $throwable->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateEmployeeTimesheet(UpdateEmployeeTimesheetScheduleRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
             */
            $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()
                ->where('id', '=', $id)
                ->first();
            if (!$employeeTimesheetSchedule) {
                return response()->json([
                    'status' => false,
                    'message' => "employee timesheet schedule not found"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var Unit $unit
             */
            $unit = Unit::query()
                ->where('relation_id', '=', $request->input('unit_relation_id'))
                ->first();
            if(!$unit) {
                return response()->json([
                    'status' => false,
                    'message' => "unit not found!"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($unit->lat == null || $unit->long == null) {
                return response()->json([
                    'status' => false,
                    'message' => "Target unit don't have location!"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $data = [
                'timesheet_date' => $request->input('timesheet_date'),
                'timesheet' => $request->input('timesheet')
            ];

            /**
             * @var EmployeeTimesheet $timesheet
             */
            $timesheet = EmployeeTimesheet::query()->where('id', '=', $data['timesheet']['id'])->first();
            if (!$timesheet) {
                return response()->json([
                    'status' => false,
                    'message' => "Timesheet not found!"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $timezone = getTimezoneV2($unit->lat, $unit->long);
            $startTime = Carbon::parse($data['timesheet_date'] . " " . $data['timesheet']['start_time'], $timezone);
            $endTime = Carbon::parse($data['timesheet_date'] . " " . $data['timesheet']['end_time'], $timezone);

            if ($endTime->isBefore($startTime)) {
                $endTime->addDays(1);
            }

            DB::beginTransaction();

            $employeeTimesheetSchedule->timesheet_id = $timesheet->id;
            $employeeTimesheetSchedule->start_time = $startTime->setTimezone('UTC')->format('Y-m-d H:i:s');
            $employeeTimesheetSchedule->end_time = $endTime->setTimezone('UTC')->format('Y-m-d H:i:s');
            $employeeTimesheetSchedule->early_buffer = $unit->early_buffer;
            $employeeTimesheetSchedule->late_buffer = $unit->late_buffer;
            $employeeTimesheetSchedule->timezone = $timezone;
            $employeeTimesheetSchedule->latitude = $unit->lat;
            $employeeTimesheetSchedule->longitude = $unit->long;
            $employeeTimesheetSchedule->unit_relation_id = $unit->relation_id;
            $employeeTimesheetSchedule->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Success!"
            ]);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $throwable->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
