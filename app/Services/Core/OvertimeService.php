<?php

namespace App\Services\Core;

use App\Http\Requests\Overtime\CreateOvertimeRequest;
use App\Http\Requests\Overtime\OvertimeApprovalRequest;
use App\Models\Overtime;
use App\Models\OvertimeEmployee;
use App\Models\OvertimeHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OvertimeService extends BaseService
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) {
        $overtimes = Overtime::query()->with(['requestorEmployee:employees.id,name']);

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($overtimes, $request)
        ], Response::HTTP_OK);
    }

    public function view(Request $request, int $id) {
        /**
         * @var Overtime $overtime
         */
        $overtime = Overtime::query()
            ->with([
                'requestorEmployee:id,name',
                'overtimeHistories', 'overtimeHistories.employee:employees.id,name',
                'overtimeEmployees', 'overtimeEmployees.employee:employees.id,name'
            ])->where('id', '=', $id)
            ->first();
        if (!$overtime) {
            return response()->json([
                'status' => false,
                'message' => "overtime Not Found"
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $overtime
        ], Response::HTTP_OK);
    }

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

                $overtime->approval_status = OvertimeHistory::TypeApproved;
                $overtime->approval_time = Carbon::now();
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

    public function approval(OvertimeApprovalRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            if (!$user->inRoleLevel([Role::RoleSuperAdministrator, Role::RoleAdmin])) {
                return response()->json([
                    'status' => false,
                    'message' => 'You don\'t have access'
                ], Response::HTTP_FORBIDDEN);
            }

            /**
             * @var Overtime $overtime
             */
            $overtime = Overtime::query()->where('id', '=', $id)->first();
            if (!$overtime) {
                return response()->json([
                    'status' => false,
                    'message' => "overtime Not Found"
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($overtime->approval_status != null) {
                return response()->json([
                    'status' => false,
                    'message' => "overtime already in approval"
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $overtime->approval_status = $request->input('status');
            $overtime->approval_time = Carbon::now();
            $overtime->save();

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtime->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = $overtime->approval_status;
            $overtimeHistory->notes = $request->input('notes');
            $overtimeHistory->save();

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
