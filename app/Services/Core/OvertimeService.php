<?php

namespace App\Services\Core;

use App\Http\Requests\Overtime\OvertimeCheckInRequest;
use App\Http\Requests\Overtime\CreateOvertimeRequest;
use App\Http\Requests\Overtime\OvertimeApprovalRequest;
use App\Http\Requests\Overtime\OvertimeCheckOutRequest;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Overtime;
use App\Models\OvertimeEmployee;
use App\Models\OvertimeHistory;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        /**
         * @var User $user
         */
        $user = $request->user();

        $overtimes = Overtime::query()->with(['requestorEmployee:employees.id,name', 'unit:units.relation_id,name']);

        if ($user->inRoleLevel([Role::RoleAdmin])) {
            $overtimes->whereIn('unit_relation_id', $user->employee->getAllUnitID());
        } else if ($user->inRoleLevel([Role::RoleStaff])) {
            $overtimes->join('overtime_employees', 'overtime_employees.overtime_id', '=', 'overtimes.id');
            $overtimes->where('overtime_employees.employee_id', '=', $user->employee_id);
        }

        $overtimes->when($request->filled('unit_name'), function(Builder $builder) use ($request) {
            $builder->join('units', 'units.relation_id', '=', 'overtimes.unit_relation_id')
                ->whereRaw('LOWER(units.name) LIKE ?', ['%'.strtolower($request->query('unit_name')).'%']);
        });
        $overtimes->when($request->filled('last_status'), function(Builder $builder) use ($request) {
            $builder->whereRaw('LOWER(overtimes.last_status) LIKE ?', ['%'.strtolower($request->query('last_status')).'%']);
        });
        $overtimes->when($request->filled('requestor_name'), function(Builder $builder) use ($request) {
            $builder->join('employees', 'employees.id', '=', 'overtimes.requestor_employee_id')
                ->whereRaw('LOWER(employees.name) LIKE ?', ['%'.strtolower($request->query('requestor_name')).'%']);
        });

        $overtimes->select(['overtimes.*']);
        $overtimes->orderBy('overtimes.id', 'DESC');

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($overtimes, $request)
        ], Response::HTTP_OK);
    }

    public function view(Request $request, int $id) {
        /**
         * @var User $user
         */
        $user = $request->user();
        $query = Overtime::query()
            ->with([
                'requestorEmployee:employees.id,name', 'unit',
                'overtimeHistories', 'overtimeHistories.employee:employees.id,name',
                'overtimeEmployees', 'overtimeEmployees.employee:employees.id,name'
            ])->where('overtimes.id', '=', $id);

        if ($user->inRoleLevel([Role::RoleAdmin])) {
            $query->whereIn('unit_relation_id', $user->employee->getAllUnitID());
        } else if ($user->inRoleLevel([Role::RoleStaff])) {
            $query->join('overtime_employees', 'overtime_employees.overtime_id', '=', 'overtimes.id');
            $query->where('overtime_employees.employee_id', '=', $user->employee_id);
            $query->select(['overtimes.*']);
        }

        $overtime = $query->first();
        if (!$overtime) {
            return response()->json([
                'status' => false,
                'message' => "overtime Not Found"
            ], Response::HTTP_BAD_REQUEST);
        }

        $overtime->append('is_can_approve');

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $overtime
        ], Response::HTTP_OK);
    }

    public function listEmployeeOvertime(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $query = OvertimeEmployee::query()
            ->with(['employee:employees.id,name', 'overtime'])
            ->join('overtimes', 'overtimes.id', '=', 'overtime_employees.overtime_id')
            ->whereNotIn('overtimes.last_status', [OvertimeHistory::TypeRejected]);

        if ($user->inRoleLevel([Role::RoleAdmin])) {
            $query->whereIn('overtimes.unit_relation_id', $user->employee->getAllUnitID());
        } else if ($user->inRoleLevel([Role::RoleStaff])) {
            $query->where('overtime_employees.employee_id', '=', $user->employee_id);
        }

        $query->when($request->filled('employee_name'), function(Builder $builder) use ($request) {
            $builder->join('employees', 'employees.id', '=', 'overtime_employees.employee_id')
                ->whereRaw('LOWER(employees.name) LIKE ?', ['%'.strtolower($request->query('employee_name')).'%']);
        });

        $query->select(['overtime_employees.*']);
        $query->orderBy('overtimes.start_datetime', 'DESC');

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($query, $request)
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

            /**
             * @var Unit $unit
             */
            $unit = Unit::query()->where('relation_id', '=', $request->input('unit_relation_id', $user->employee->getLastUnitID()))->first();
            if (!$unit) {
                return response()->json([
                    'status' => false,
                    'message' => "Unit Not Found"
                ], Response::HTTP_BAD_REQUEST);
            }

            /**
             *  NOTES:
             *
             *  For start_datetime and end_datetime need to unified to be UTC, this changes be more useful for some reasons,
             *  especially for some Users has different timezones. With unified timezone, it will be easier to make sorting
             *  datetime, and also to matching with User current location timezone.
             */

            $unitTimeZone = getTimezoneV2($unit->lat, $unit->long);
            $startTime = Carbon::parse($request->input('start_datetime'), $unitTimeZone)->setTimezone('UTC');
            $endTime = Carbon::parse($request->input('end_datetime'), $unitTimeZone)->setTimezone('UTC');

            $employeeIDs = $request->input('employees', []);

            /**
             * @var OvertimeEmployee[] $employeeExistingOvertimes
             */
            $employeeExistingOvertimes = OvertimeEmployee::query()
                ->join('overtimes', 'overtimes.id', '=', 'overtime_employees.overtime_id')
                ->whereIn('overtime_employees.employee_id', $employeeIDs)
                ->where(function(Builder $builder) use ($request, $startTime, $endTime) {
                    $builder->orWhere(function (Builder $query) use ($request, $startTime, $endTime) {
                        $query->where('overtimes.start_datetime', '<=', $startTime->format('Y-m-d H:i:s'))
                            ->where('overtimes.end_datetime', '>=', $startTime->format('Y-m-d H:i:s'));
                    })->orWhere(function (Builder $query) use ($request, $startTime, $endTime) {
                        $query->where('overtimes.start_datetime', '<=', $endTime->format('Y-m-d H:i:s'))
                            ->where('overtimes.end_datetime', '>=', $endTime->format('Y-m-d H:i:s'));
                    });
                })
                ->whereNull('overtime_employees.check_in_time')
                ->where(function(Builder $builder) {
                    $builder->orWhereNotIn('overtimes.last_status', [OvertimeHistory::TypeRejected, OvertimeHistory::TypeFinished])
                        ->orWhereNull('overtimes.last_status');
                })
                ->select(['overtime_employees.*'])
                ->orderBy('overtimes.start_datetime', 'ASC')
                ->get();

            foreach ($employeeExistingOvertimes as $employeeExistingOvertime) {
                return response()->json([
                    'status' => false,
                    'message' => sprintf("%s has active overtime on that time", $employeeExistingOvertime->employee->name)
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $overtime = new Overtime();
            $overtime->requestor_employee_id = $user->employee_id;
            $overtime->unit_relation_id = $unit->relation_id;
            $overtime->start_datetime = $startTime;
            $overtime->end_datetime = $endTime;
            $overtime->timezone = $unitTimeZone;
            $overtime->notes = $request->input('notes');
            $overtime->image_url = $request->input('image_url');
            $overtime->location_lat = $unit->lat;
            $overtime->location_long = $unit->long;
            $overtime->save();

            foreach ($employeeIDs as $employeeID) {
                $overtimeEmployee = new OvertimeEmployee();
                $overtimeEmployee->overtime_id = $overtime->id;
                $overtimeEmployee->employee_id = $employeeID;
                $overtimeEmployee->save();
            }

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtime->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = OvertimeHistory::TypePending;
            $overtimeHistory->save();

            if ($user->inRoleLevel([Role::RoleSuperAdministrator, Role::RoleAdmin])) {
                $overtimeHistory = new OvertimeHistory();
                $overtimeHistory->overtime_id = $overtime->id;
                $overtimeHistory->employee_id = $user->employee_id;
                $overtimeHistory->history_type = OvertimeHistory::TypeApproved;
                $overtimeHistory->save();
            }

            $overtime->last_status = $overtimeHistory->history_type;
            $overtime->last_status_at = Carbon::now();
            $overtime->save();

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

    /**
     * @param OvertimeApprovalRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
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

            $query = Overtime::query()->where('overtimes.id', '=', $id)
                ->where('last_status', '=', OvertimeHistory::TypePending);
            if ($user->inRoleLevel([Role::RoleAdmin])) {
                $query->whereIn('unit_relation_id', $user->employee->getAllUnitID());
            }

            /**
             * @var Overtime $overtime
             */
            $overtime = $query->first();
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

            $overtime->last_status = $request->input('status');
            $overtime->last_status_at = Carbon::now();
            $overtime->save();

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtime->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = $overtime->last_status;
            $overtimeHistory->notes = $request->input('notes');
            $overtimeHistory->save();

            $this->refreshFinishedStatus($overtime, $user->employee_id);

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

    public function checkIn(OvertimeCheckInRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $dataLocation = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude')
            ];

            /**
             * @var OvertimeEmployee $employeeOvertime
             */
            $employeeOvertime = OvertimeEmployee::query()
                ->join('overtimes', 'overtimes.id', '=', 'overtime_employees.overtime_id')
                ->where('overtime_employees.employee_id', '=', $user->employee_id)
                ->where('overtimes.end_datetime', '>', Carbon::now()->format('Y-m-d H:i:s'))
                ->whereNull('overtime_employees.check_in_time')
                ->where(function(Builder $builder) {
                    $builder->orWhereNotIn('overtimes.last_status', [OvertimeHistory::TypeRejected, OvertimeHistory::TypeFinished])
                        ->orWhereNull('overtimes.last_status');
                })
                ->select(['overtime_employees.*'])
                ->orderBy('overtimes.start_datetime', 'ASC')
                ->first();
            if (!$employeeOvertime) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have any overtime need to check-in"
                ], Response::HTTP_BAD_REQUEST);
            }

            $overtimeRequest = $employeeOvertime->overtime;
            $employeeTimezone = getTimezoneV2($dataLocation['latitude'], $dataLocation['longitude']);

            $workLocation = $user->employee->getLastUnit();
            $distance = calculateDistance($overtimeRequest->location_lat, $overtimeRequest->location_long, $dataLocation['latitude'], $dataLocation['longitude']);

            $checkInType = EmployeeAttendance::TypeOnSite;
            if ($distance > $workLocation->radius) {
                $checkInType = EmployeeAttendance::TypeOffSite;
            }

            DB::beginTransaction();

            $employeeOvertime->check_in_lat = $dataLocation['latitude'];
            $employeeOvertime->check_in_long = $dataLocation['longitude'];
            $employeeOvertime->check_in_time = Carbon::now();
            $employeeOvertime->check_in_timezone = $employeeTimezone;
            $employeeOvertime->save();

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtimeRequest->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = OvertimeHistory::TypeCheckIn;
            $overtimeHistory->save();

            $checkIn = new EmployeeAttendance();
            $checkIn->employee_id = $user->employee_id;
            $checkIn->real_check_in = $employeeOvertime->check_in_time;
            $checkIn->checkin_type = $checkInType;
            $checkIn->checkin_lat = $employeeOvertime->check_in_lat;
            $checkIn->checkin_long = $employeeOvertime->check_in_long;
            $checkIn->is_need_approval = $checkInType == EmployeeAttendance::TypeOffSite;
            $checkIn->attendance_types = EmployeeAttendance::AttendanceTypeOvertime;
            $checkIn->checkin_real_radius = $distance;
            $checkIn->approved = !($checkInType == EmployeeAttendance::TypeOffSite);
            $checkIn->check_in_tz = $employeeTimezone;
            $checkIn->is_late = false;
            $checkIn->late_duration = 0;
            $checkIn->save();

            $this->refreshFinishedStatus($overtimeRequest, $user->employee_id);

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

    public function checkOut(OvertimeCheckOutRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $dataLocation = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude')
            ];

            /**
             * @var OvertimeEmployee $employeeOvertime
             */
            $employeeOvertime = OvertimeEmployee::query()
                ->join('overtimes', 'overtimes.id', '=', 'overtime_employees.overtime_id')
                ->where('overtime_employees.employee_id', '=', $user->employee_id)
                ->where('overtimes.end_datetime', '<=', Carbon::now()->addMinutes(60)->format('Y-m-d H:i:s'))
                ->whereNull('overtime_employees.check_out_time')
                ->whereNotNull('overtime_employees.check_in_time')
                ->where(function(Builder $builder) {
                    $builder->orWhereNotIn('overtimes.last_status', [OvertimeHistory::TypeRejected, OvertimeHistory::TypeFinished])
                        ->orWhereNull('overtimes.last_status');
                })
                ->select(['overtime_employees.*'])
                ->orderBy('overtimes.start_datetime', 'ASC')
                ->first();
            if (!$employeeOvertime) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have any overtime need to check-out"
                ], Response::HTTP_BAD_REQUEST);
            }

            $overtimeRequest = $employeeOvertime->overtime;
            $employeeTimezone = getTimezoneV2($dataLocation['latitude'], $dataLocation['longitude']);

            /**
             * @var EmployeeAttendance $checkInData
             */
            $checkInData = $user->employee->attendances()
                ->where('attendance_types', '=', EmployeeAttendance::AttendanceTypeOvertime)
                ->orderBy('id', 'DESC')
                ->first();

            $workLocation = $user->employee->getLastUnit();
            $distance = calculateDistance($overtimeRequest->location_lat, $overtimeRequest->location_long, $dataLocation['latitude'], $dataLocation['longitude']);

            $checkOutType = EmployeeAttendance::TypeOnSite;
            if ($distance > $workLocation->radius) {
                $checkOutType = EmployeeAttendance::TypeOffSite;
            }

            DB::beginTransaction();

            $employeeOvertime->check_out_lat = $dataLocation['latitude'];
            $employeeOvertime->check_out_long = $dataLocation['longitude'];
            $employeeOvertime->check_out_time = Carbon::now();
            $employeeOvertime->check_out_timezone = $employeeTimezone;
            $employeeOvertime->save();

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtimeRequest->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = OvertimeHistory::TypeCheckOut;
            $overtimeHistory->save();

            if ($checkInData) {
                $checkInData->real_check_out = $employeeOvertime->check_out_time;
                $checkInData->checkout_lat = $employeeOvertime->check_out_lat;
                $checkInData->checkout_long = $employeeOvertime->check_out_long;
                $checkInData->checkout_real_radius = $distance;
                $checkInData->checkout_type = $checkOutType;
                $checkInData->check_out_tz = $employeeOvertime->check_out_timezone;
                $checkInData->save();
            }

            $this->refreshFinishedStatus($overtimeRequest, $user->employee_id);

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

    private function refreshFinishedStatus(Overtime  $overtime, int $employeeID) {
        $overtime->refresh();

        $isEmployeeFinishedAttendance = true;

        foreach ($overtime->overtimeEmployees as $overtimeEmployee) {
            if (is_null($overtimeEmployee->check_in_time) || is_null($overtimeEmployee->check_out_time)) {
                $isEmployeeFinishedAttendance = false;
            }
        }

        $isFinished = $overtime->last_status == OvertimeHistory::TypeApproved && $isEmployeeFinishedAttendance;
        if ($isFinished) {
            $overtime->last_status = OvertimeHistory::TypeFinished;
            $overtime->last_status_at = Carbon::now();
            $overtime->save();

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtime->id;
            $overtimeHistory->employee_id = $employeeID;
            $overtimeHistory->history_type = OvertimeHistory::TypeFinished;
            $overtimeHistory->save();
        }
    }
}
