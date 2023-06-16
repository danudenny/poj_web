<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Rules\UniqueCheckInToday;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeAttendanceService extends BaseService {

    /**
     * @throws Exception
     */
    public function checkIn($request, $id): JsonResponse
    {
        $empData = Employee::find($id);
        if (!$empData) {
            return response()->json([
                'message' => 'Employee not found!'
            ], 400);
        }

        $workLocation = $empData->company->workLocation;
        $employeeDetail = $empData->employeeDetail;

        // Check if time is in range
        $employeeTimesheetCheckin = Carbon::createFromTimeString($employeeDetail->employeeTimesheet->start_time);
        $employeeTimesheetCheckout = Carbon::createFromTimeString($employeeDetail->employeeTimesheet->end_time);
        $pareseRequestedTime = Carbon::parse($request->real_check_in)->toTimeString('minutes');
        $requestedTime = Carbon::createFromTimeString($pareseRequestedTime);

        if (!$requestedTime->between($employeeTimesheetCheckin->subMinutes(15), $employeeTimesheetCheckout->subMinutes(15))) {
            return response()->json([
                'message' => 'Check in time must be between ' . $employeeTimesheetCheckin->toTimeString('minutes') . ' and ' . $employeeTimesheetCheckout->toTimeString('minutes')
            ], 400);
        }
        // Check if time is in range

        // Check if employee has checked in today
        $eightHoursAgo = Carbon::now()->addHours(8)->toDateTimeString();
        $checkInData = EmployeeAttendance::where('employee_id', $id)->first();

        if ($checkInData) {
            if ($checkInData->real_check_in <= $eightHoursAgo && $request->attendance_types == 'normal') {
                return response()->json([
                    'message' => 'The employee has already checked in today.'
                ], 400);
            }
        }
        // Check if employee has checked in today

        // Calulate distance
        $distance = calculateDistance($request->lat, $request->long, floatval($workLocation->lat), floatval($workLocation->long));

        $attType = "";
        $isNeedApproval = false;
        if ($distance <= intval($workLocation->radius)){
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
            $checkIn->checkin_real_radius = $distance;

            if (!$checkIn->save()) {
                throw new Exception('Failed save data!');
            }

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

    public function checkOut($request, $id): JsonResponse
    {
        $empData = Employee::find($id);
        if (!$empData) {
            return response()->json([
                'message' => 'Employee not found!'
            ], 400);
        }

        $workLocation = $empData->company->workLocation;

        $checkInData = EmployeeAttendance::where('employee_id', $id)->first();
        if (!$checkInData) {
            return response()->json([
                'message' => 'The employee has not checked in today.'
            ], 400);
        }

        $distance = calculateDistance(
            $request->lat, $request->long,
            floatval($workLocation->lat),
            floatval($workLocation->long)
        );

        DB::beginTransaction();
        try {
            $checkInData->real_check_out = $request->real_check_out;
            $checkInData->duration = Carbon::parse($checkInData->real_check_in)->diffInMinutes(Carbon::parse($request->real_check_out));
            $checkInData->checkout_lat = $request->lat;
            $checkInData->checkout_long = $request->long;
            $checkInData->checkout_real_radius = $distance;

            if (!$checkInData->save()) {
                throw new Exception('Failed save data!');
            }

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
