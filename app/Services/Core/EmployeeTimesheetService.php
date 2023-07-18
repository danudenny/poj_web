<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\EmployeeTimesheet;
use App\Models\EmployeeTimesheetSchedule;
use App\Models\Period;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class EmployeeTimesheetService extends BaseService {
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    public function index($request, $id): JsonResponse
    {
        try {
            $data = EmployeeTimesheet::query();
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
            $data->where('unit_id', $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Data fetched successfully',
                'data' => $data->paginate(request()->get('limit') ?? 10)
            ], 200);
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

            $data = new EmployeeTimesheet();
            $data->unit_id = $id;
            $data->name = $request->name;
            $data->start_time = $request->start_time;
            $data->end_time = $request->end_time;
            $data->is_active = $request->is_active;

            if (!$data->save()) {
                throw new Exception('Failed to save data');
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data saved successfully',
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
    public function show($unit_id, $id): JsonResponse
    {
        try {
            $timesheet = EmployeeTimesheet::where('unit_id', $unit_id)
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
            $data = EmployeeTimesheet::find($id);
            $data->name = $request->name;
            $data->start_time = $request->start_time;
            $data->end_time = $request->end_time;
            $data->is_active = $request->is_active ?? true;

            if (!$data->save()) {
                throw new Exception('Failed to update data');
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully',
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
    public function delete($id): JsonResponse
    {
        $dataExists = EmployeeTimesheet::where('id', $id)->first();
        if (!$dataExists) {
            throw new Exception('Data not found');
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
        $timesheetExists = EmployeeTimesheet::where('id', $request->timesheet_id)->first();
        if (!$timesheetExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timesheet id not found',
                'data' => ''
            ], 404);
        }

        $periodsExists = Period::where('id', $request->period_id)->first();
        if (!$periodsExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Period id not found',
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

                $schedule = new EmployeeTimesheetSchedule();
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

    public function getEmployeeSchedule($request): JsonResponse
    {
        $roles = Auth::user();
        $groupedData = [];
        $getMonth = Carbon::now()->format('m');
        $schedule = EmployeeTimesheetSchedule::query();
        $schedule->with(['employee', 'employee.kanwil', 'employee.area', 'employee.cabang','employee.outlet','timesheet', 'period']);
        if ($roles->hasRole('superadmin')) {
//            $getPeriod = Period::where('id', request()->query('period_id'))->first();
            $schedule = $schedule
                ->orderBy('date')
                ->orderBy('period_id')
                ->get();
            $groupedData = $schedule->groupBy(function ($item) {
                $year = $item->period->year;
                $month = $item->period->month;
                $getDate = Carbon::parse($year . '-' . $month);
                return $item->date . '-' . $getDate->format('F Y');
            });
        } else if ($roles->hasRole('admin')) {
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
            'data' => $groupedData
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

    public function showEmployeeScheduleById($request): JsonResponse
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

    public function getPeriods(): JsonResponse
    {
        $currentYear = Carbon::now()->year;
        $periods = Period::where('year', $currentYear)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data fetched successfully',
            'data' => $periods
        ], 200);
    }
}
