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
    public function index($request): JsonResponse
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
    public function save($request): JsonResponse
    {

        DB::beginTransaction();
        try {
            $dataExsists = EmployeeTimesheet::where('name', $request->name)->first();
            if ($dataExsists) {
                throw new Exception('Timesheet Name already exists');
            }

            $data = new EmployeeTimesheet();
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
    public function show($id): JsonResponse
    {
        try {
            $timesheet = EmployeeTimesheet::where('id', $id)->first();
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
        $schedule = EmployeeTimesheetSchedule::with(['employee', 'timesheet', 'period'])
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data fetched successfully',
            'data' => $schedule
        ], 200);
    }

    public function showEmployeeSchedule($request): JsonResponse
    {
        $schedule = EmployeeTimesheetSchedule::with(['employee', 'timesheet', 'period'])
            ->where('date', $request->date)
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
