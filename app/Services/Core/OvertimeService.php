<?php

namespace App\Services\Core;

use App\Http\Requests\Overtime\CreateOvertimeRequest;
use App\Models\Overtime;
use App\Models\OvertimeEmployee;
use App\Models\OvertimeHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OvertimeService extends BaseService
{

    /**
     * @param CreateOvertimeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateOvertimeRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $requestorUnit = $user->employee->getLastUnit();

            DB::beginTransaction();

            $overtime = new Overtime();
            $overtime->requestor_employee_id = $user->employee_id;
            $overtime->date_overtime = $request->input('date_overtime');
            $overtime->start_time = $request->input('start_time');
            $overtime->end_time = $request->input('end_time');
            $overtime->notes = $request->input('notes');
            $overtime->image_url = $request->input('image_url');
            $overtime->location_lat = $requestorUnit->lat;
            $overtime->location_long = $requestorUnit->long;
            $overtime->save();

            foreach ($request->input('employees', []) as $employeeID) {
                $overtimeEmployee = new OvertimeEmployee();
                $overtimeEmployee->overtime_id = $overtime->id;
                $overtimeEmployee->employee_id = $employeeID;
                $overtimeEmployee->save();
            }

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtime->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = OvertimeHistory::TypeSubmitted;
            $overtimeHistory->save();

            if ($user->hasRole([Role::RoleSuperAdministrator, Role::RoleAdmin])) {
                $overtimeHistory = new OvertimeHistory();
                $overtimeHistory->overtime_id = $overtime->id;
                $overtimeHistory->employee_id = $user->employee_id;
                $overtimeHistory->history_type = OvertimeHistory::TypeApproved;
                $overtimeHistory->save();

                $overtime->approved_at = Carbon::now();
                $overtime->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
