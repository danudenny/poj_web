<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceHistory;
use App\Models\LateCheckin;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkReporting;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class EmployeeAttendanceService extends BaseService {


    public function index($request): JsonResponse
    {
        $roles = Auth::user()->roles;
        try {
            $attendances = EmployeeAttendance::query();
            $attendances->with(['employee', 'employee.kanwil', 'employee.area', 'employee.cabang', 'employee.outlet', 'employee.employeeDetail', 'employee.employeeDetail.employeeTimesheet', 'employeeAttendanceHistory']);
            $attendances->when($request->name, function ($query) use ($request) {
                $query->whereHas('employee', function (Builder $query) use ($request) {
                    $query->whereRaw('LOWER(name) LIKE ?', [strtolower('%' . request()->query('name') . '%')]);
                });
            });
            $attendances->when($request->check_in, function ($query) use ($request) {
                $query->whereDate('real_check_in', '>=', $request->check_in);
            });
            $attendances->when($request->check_out, function ($query) use ($request) {
                $query->whereDate('real_check_out', '<=', $request->check_out);
            });
            $attendances->when($request->attendance_types, function ($query) use ($request) {
                $query->where('attendance_types', $request->attendance_types);
            });
            $attendances->when($request->checkin_type, function ($query) use ($request) {
                $query->where('checkin_type', $request->checkin_type);
            });
            $attendances->when($request->is_need_approval, function ($query) use ($request) {
                $query->where('is_need_approval', $request->is_need_approval);
            });
            $attendances->orderBy('created_at', 'desc');


            foreach ($roles as $role) {
                if ($role->role_level === 'superadmin') {
                    $attendances = $attendances->paginate($request->get('limit', 10));
                } else if ($role->role_level === 'staff') {
                    $attendances = $attendances->where('employee_id', Auth::user()->employee_id)
                        ->paginate($request->get('limit', 10));
                } else if ($role->role_level === 'admin') {
                    $attendances = $attendances->whereHas('employee', function (Builder $query) use ($roles) {
                        $query->where('kanwil_id', $roles->kanwil_id)
                            ->orWhere('area_id', $roles->area_id)
                            ->orWhere('cabang_id', $roles->cabang_id)
                            ->orWhere('outlet_id', $roles->outlet_id);
                    });
                } else {
                    $attendances = $attendances->where('employee_id', Auth::user()->employee_id)
                        ->paginate($request->get('limit', 10));
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Success get data!',
                'data' => $attendances
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => self::SOMETHING_WRONG.' : '.$e->getMessage()
            ], 500);
        }
    }

    function getLastUnit($data) {
        $bottomData = null;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nestedData = $this->getLastUnit($value);
                if ($nestedData !== null) {
                    $bottomData = $nestedData;
                }
            } elseif ($key === 'value' && $value !== null) {
                $bottomData = $data;
            }
        }

        return $bottomData;
    }
    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function checkIn($request, $id): JsonResponse
    {
        $empData = Employee::with(['kanwil', 'area', 'cabang', 'outlet', 'timesheetSchedules', 'timesheetSchedules.period', 'timesheetSchedules.timesheet'])->find($id);
        if (!$empData) {
            return response()->json([
                'message' => 'Employee not found!'
            ], 400);
        }

        $decodedEmpData = json_decode($empData, true);
        $filteredUnitData = Arr::only($decodedEmpData, ['kanwil', 'area', 'cabang', 'outlet']);

        $timesheetSchedules = $decodedEmpData['timesheet_schedules'];

        $currentDate = Carbon::now()->format('Y-m-d');
        $matchingSchedule = null;

        foreach ($timesheetSchedules as $schedule) {
            $day = $schedule['date'];
            $year = $schedule['period']['year'];
            $month = $schedule['period']['month'];

            $carbonDate = Carbon::create($year, $month, $day)->format('Y-m-d');

            if ($carbonDate === $currentDate) {
                $matchingSchedule = $schedule;
            }
        }

        $empSchedule = $matchingSchedule;
        $empUnit = $this->getLastUnit($filteredUnitData);

        $workLocation = $empUnit;

        // Check if time is in range
        if (!$empSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee schedule not found! Contact Administrator for Help!'
            ], 400);
        }
        $employeeTimesheetCheckin = Carbon::parse($empSchedule['timesheet']['start_time']);
        $employeeTimesheetCheckout = Carbon::parse($empSchedule['timesheet']['end_time']);
        if ($empSchedule['timesheet']['end_time'] < $empSchedule['timesheet']['start_time']) {
            $employeeTimesheetCheckout = Carbon::parse($empSchedule['timesheet']['end_time'])->addDay();
        }
        $parseRequestedTime = Carbon::parse($request->real_check_in);
        $requestedTime = Carbon::createFromTimeString($parseRequestedTime);

        $adjustedCheckin = $employeeTimesheetCheckin->copy()->subMinutes($workLocation['early_buffer']);
        $adjustedCheckout = $employeeTimesheetCheckout->copy()->subMinutes($workLocation['late_buffer']);

        if (!$parseRequestedTime->between($adjustedCheckin, $adjustedCheckout)) {
            return response()->json([
                'message' => 'Check in time must be between ' . $employeeTimesheetCheckin->toTimeString('minutes') . ' and ' . $employeeTimesheetCheckout->toTimeString('minutes')
            ], 400);
        }
        // Check if time is in range

        // Check if employee has checked in today
        $checkInData = EmployeeAttendance::where('employee_id', $id)->orderBy('id', 'desc')->first();

        if (!$request->real_check_in) {
            if ($request->attendance_types == 'normal' && Carbon::parse($checkInData->real_check_in)->format('Y-m-d') == Carbon::parse($parseRequestedTime)->format('Y-m-d')) {
                return response()->json([
                    'message' => 'You have checked in today!'
                ], 400);
            }
        }
        // Check if employee has checked in today

        // Calulate distance
        if ($workLocation['lat'] === null && $workLocation['long'] === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work location not found!'
            ], 400);
        }

        $distance = calculateDistance($request->lat, $request->long, floatval($workLocation['lat']), floatval($workLocation['long']));

        $employeeTimeZone = getTimezone($request->lat, $request->long);
        $companyTimeZone = getTimezone($workLocation['lat'], $workLocation['long']);

        $earlyTolerance = $workLocation['early_buffer'];
        $lateTolerance = $workLocation['late_buffer'];

        $employeeCheckInTime = Carbon::parse($request->real_check_in, $employeeTimeZone)->setTimezone($companyTimeZone);
        $companyCheckInTime = Carbon::createFromFormat('H:i', $empSchedule['timesheet']['start_time'], $companyTimeZone);

        $earlyBoundary = $companyCheckInTime->copy()->subMinutes($earlyTolerance);
        $lateBoundary = $companyCheckInTime->copy()->addMinutes($lateTolerance);

        $lateDifference = $lateBoundary->diffInMinutes($employeeCheckInTime, false);

        // Compare the employee's check-in time with the boundaries
        $isOnTime = $employeeCheckInTime->between($earlyBoundary, $lateBoundary, true);

        $attType = "";
        $isNeedApproval = false;
        if ($distance <= intval($workLocation['radius'])){
           $attType = "onsite";
            $isNeedApproval = false;
        } else {
            $attType = "offsite";
            $isNeedApproval = true;
        }

        DB::beginTransaction();
        try {
            $checkIn = new EmployeeAttendance();
            $checkIn->employee_id = $id;
            $checkIn->real_check_in = $request->real_check_in;
            $checkIn->checkin_type = $attType;
            $checkIn->checkin_lat = $request->lat;
            $checkIn->checkin_long = $request->long;
            $checkIn->is_need_approval = $isNeedApproval;
            $checkIn->attendance_types = $request->attendance_types;
            $checkIn->checkin_real_radius = max(0, $distance);
            $checkIn->approved = !$isNeedApproval;
            $checkIn->check_in_tz = $employeeTimeZone;
            $checkIn->is_late = Carbon::parse($requestedTime)->lessThan(Carbon::parse($empSchedule['timesheet']['start_time']));
           if ($requestedTime->lessThan($employeeCheckInTime)) {
                $checkIn->late_duration = $requestedTime->diffInMinutes($employeeCheckInTime);
            } else {
                $checkIn->late_duration = 0;
            }
            $checkIn->is_on_time = $isOnTime;

            if (!$checkIn->save()) {
                throw new Exception('Failed save data!');
            }

//            if ($lateDifference >= 15 && $lateDifference <= 120) {
//                $columnName = 'late_' . $lateDifference;
//                $totalLateColumn = 'total_' . $columnName;
//                $year = $employeeCheckInTime->year;
//                $month = $employeeCheckInTime->month;
//                LateCheckin::where('employee_id', $id)->increment($columnName, $lateDifference, [
//                    'year' => $year,
//                    'month' => $month,
//                    $totalLateColumn => $lateDifference,
//                ]);
//            }

            $getUser = User::where('employee_id', $id)->first();
            $getUser->is_normal_checkin = true;
            $getUser->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Success save data!',
                'data' => $checkIn
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     */
    public function checkOut($request, $id): JsonResponse
    {
        $empData = Employee::with(['kanwil', 'area', 'cabang', 'outlet', 'timesheetSchedules', 'timesheetSchedules.period', 'timesheetSchedules.timesheet'])->find($id);
        if (!$empData) {
            return response()->json([
                'message' => 'Employee not found!'
            ], 400);
        }

        $decodedEmpData = json_decode($empData, true);
        $filteredUnitData = Arr::only($decodedEmpData, ['kanwil', 'area', 'cabang', 'outlet']);
        $timesheetSchedules = $decodedEmpData['timesheet_schedules'];

        $currentDate = Carbon::now()->addDay();
        $matchingSchedule = null;

        foreach ($timesheetSchedules as $schedule) {
            $day = $schedule['date'];
            $year = $schedule['period']['year'];
            $month = $schedule['period']['month'];

            $carbonDate = Carbon::create($year, $month, $day);

            if ($carbonDate->isSameDay($currentDate)) {
                $matchingSchedule = $schedule;
            }
        }

        $empSchedule = $matchingSchedule;
        $empUnit = $this->getLastUnit($filteredUnitData);

        $workLocation = $empUnit;

        // FIXME: Check if time is in range
        if (!$empSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schedule not found!'
            ], 400);
        }
        $employeeTimesheetCheckin = Carbon::parse($empSchedule['timesheet']['start_time']);
        $employeeTimesheetCheckout = Carbon::parse($empSchedule['timesheet']['end_time']);
        if ($empSchedule['timesheet']['end_time'] < $empSchedule['timesheet']['start_time']) {
            $employeeTimesheetCheckout = Carbon::parse($empSchedule['timesheet']['end_time'])->addDay();
        }
        $parseRequestedTime = Carbon::parse($request->real_check_out);
        $requestedTime = Carbon::createFromTimeString($parseRequestedTime);
        $adjustedCheckin = $employeeTimesheetCheckin->copy()->subMinutes($workLocation['early_buffer']);

        if (!$parseRequestedTime->greaterThan($adjustedCheckin)) {
            return response()->json([
                'message' => 'Check out time must be greater than ' . $employeeTimesheetCheckin->toTimeString('minutes')
            ], 400);
        }

        //check if employee has submit worklocation today
        $workReporting = WorkReporting::where('employee_id', $id)->whereDate('date', Carbon::today())->first();
        if (!$workReporting) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have not submit work reporting today!'
            ], 400);
        }

        $checkInData = EmployeeAttendance::where('employee_id', $id)->whereNull('real_check_out')->first();
        if (!$checkInData) {
            return response()->json([
                'message' => 'The employee has not checked in today.'
            ], 400);
        }

        if ($workLocation['lat'] === null && $workLocation['long'] === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work location not found!'
            ], 400);
        }

        $distance = calculateDistance(
            $request->lat, $request->long,
            floatval($workLocation['lat']),
            floatval($workLocation['long'])
        );

        $employeeTimeZone = getTimezone($request->lat, $request->long);

        DB::beginTransaction();
        try {
            $checkInData->real_check_out = $request->real_check_out;
            $checkInData->checkout_lat = $request->lat;
            $checkInData->checkout_long = $request->long;
            $checkInData->checkout_real_radius = $distance;
            $checkInData->check_out_tz = $employeeTimeZone;
            $checkInData->is_early = Carbon::parse($request->real_check_out)->lessThan(Carbon::parse($empSchedule['timesheet']['end_time']));
            if ($requestedTime->lessThan($employeeTimesheetCheckout)) {
                $checkInData->early_duration = $requestedTime->diffInMinutes($employeeTimesheetCheckout);
            } else {
                $checkInData->early_duration = 0;
            }
            $checkInData->duration = Carbon::parse($checkInData->real_check_in)->diffInMinutes(Carbon::parse($checkInData->real_check_out));

            if (!$checkInData->save()) {
                throw new Exception('Failed save checkout data!');
            }

            $attHistory = new EmployeeAttendanceHistory();
            $attHistory->employee_id = $id;
            $attHistory->employee_attendances_id = $checkInData->id;
            $attHistory->status = $checkInData->approved ? 'approved' : 'pending';

            if (!$attHistory->save()) {
                throw new Exception('Failed save attendance histories data!');
            }

            $getUser = User::where('employee_id', $id)->first();
            $getUser->is_normal_checkin = false;
            $getUser->is_normal_checkout = true;
            $getUser->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success save data!',
                'data' => $checkInData
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed save data!',
                'data' => $e->getMessage()
            ]);
        }
    }

}
