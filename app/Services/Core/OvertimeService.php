<?php

namespace App\Services\Core;

use App\Http\Requests\Overtime\OvertimeCheckInRequest;
use App\Http\Requests\Overtime\CreateOvertimeRequest;
use App\Http\Requests\Overtime\OvertimeApprovalRequest;
use App\Http\Requests\Overtime\OvertimeCheckOutRequest;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Job;
use App\Models\EmployeeNotification;
use App\Models\Overtime;
use App\Models\OvertimeDate;
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
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

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

        if ($user->inRoleLevel([Role::RoleStaff])) {
            $overtimes->join('overtime_dates', 'overtime_dates.overtime_id', '=', 'overtimes.id');
            $overtimes->join('overtime_employees', 'overtime_employees.overtime_date_id', '=', 'overtime_dates.id');
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
                'requestorEmployee', 'unit',
                'overtimeHistories', 'overtimeHistories.employee:employees.id,name',
                'overtimeDates', 'overtimeDates.overtimeEmployees', 'overtimeDates.overtimeEmployees.employee:employees.id,name'
            ])->where('overtimes.id', '=', $id);

        if ($user->isHighestRole(Role::RoleStaff)) {
            $query->join('overtime_dates', 'overtime_dates.overtime_id', '=', 'overtimes.id');
            $query->join('overtime_employees', 'overtime_employees.overtime_date_id', '=', 'overtime_dates.id');
            $query->where('overtime_employees.employee_id', '=', $user->employee_id);
        }

        $query->select(['overtimes.*']);

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
            ->with(['employee:employees.id,name', 'overtimeDate.overtime'])
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
            ->whereNotIn('overtimes.last_status', [OvertimeHistory::TypeRejected]);

        if ($user->isHighestRole(Role::RoleStaff)) {
            $query->where('overtime_employees.employee_id', '=', $user->employee_id);
        }

        $query->select(['overtime_employees.*']);
        $query->orderBy('overtimes.start_date', 'DESC');

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
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var Job $job
             */
            $job = Job::query()->where('id', '=', $request->input('job_id'))->first();
            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job not exist',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             *  NOTES:
             *
             *  For start_datetime and end_datetime need to unified to be UTC, this changes be more useful for some reasons,
             *  especially for some Users has different timezones. With unified timezone, it will be easier to make sorting
             *  datetime, and also to matching with User current location timezone.
             */

            $unitTimeZone = getTimezoneV2($unit->lat, $unit->long);

            $employeeIDs = $request->input('employee_ids', []);
            $overtimeDates = $this->generateOvertimeDateData($request->input('dates'), $employeeIDs, $unitTimeZone);
            if (count($overtimeDates) == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no dates',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $overtime = new Overtime();
            $overtime->requestor_employee_id = $user->employee_id;
            $overtime->unit_relation_id = $unit->relation_id;
            $overtime->job_id = $job->id;
            $overtime->start_date = $overtimeDates[0]['date'];
            $overtime->end_date = $overtimeDates[count($overtimeDates) - 1]['date'];
            $overtime->last_status = OvertimeHistory::TypePending;
            $overtime->last_status_at = Carbon::now();
            $overtime->location_lat = $unit->lat;
            $overtime->location_long = $unit->long;
            $overtime->timezone = $unitTimeZone;
            $overtime->notes = $request->input('notes');
            $overtime->image_url = $request->input('image_url');
            $overtime->save();

            foreach ($overtimeDates as $overtimeDateData) {
                $overtimeDate = new OvertimeDate();
                $overtimeDate->overtime_id = $overtime->id;
                $overtimeDate->date = $overtimeDateData['date'];
                $overtimeDate->start_time = $overtimeDateData['start_time'];
                $overtimeDate->end_time = $overtimeDateData['end_time'];
                $overtimeDate->save();

                foreach ($overtimeDateData['employee_ids'] as $employeeID) {
                    $backupEmployeeTime = new OvertimeEmployee();
                    $backupEmployeeTime->employee_id = $employeeID;
                    $backupEmployeeTime->overtime_date_id = $overtimeDate->id;
                    $backupEmployeeTime->save();
                }
            }

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtime->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = $overtime->last_status;
            $overtimeHistory->save();

            if ($user->inRoleLevel([Role::RoleSuperAdministrator, Role::RoleAdmin])) {
                $overtime->last_status = OvertimeHistory::TypeApproved;
                $overtime->last_status_at = Carbon::now();
                $overtime->save();

                $overtimeHistory = new OvertimeHistory();
                $overtimeHistory->overtime_id = $overtime->id;
                $overtimeHistory->employee_id = $user->employee_id;
                $overtimeHistory->history_type = $overtime->last_status;
                $overtimeHistory->save();
            }

            foreach ($employeeIDs as $employeeID) {
                $this->getNotificationService()->createNotification(
                    $employeeID,
                    'Pelaksanaan Lembur',
                    count($overtimeDates) == 0 ? $overtimeDates[0]['date'] : sprintf("%s - %s", $overtimeDates[0]['date'], $overtimeDates[count($overtimeDates) - 1]['date']),
                    'Lembur Pegawai',
                    EmployeeNotification::ReferenceOvertime,
                    $overtime->id
                )->withSendPushNotification()->send();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array[] $dates
     * @param int[] $employeeIDs
     * @param string $timezone
     * @return array
     */
    private function generateOvertimeDateData(array $dates, array $employeeIDs, string $timezone): array {
        $results = [];
        $sortedDates = array_keys($dates);
        sort($sortedDates);

        foreach ($sortedDates as $sortedDate) {
            $data = $dates[$sortedDate];

            $startTime = Carbon::createFromFormat('Y-m-d H:i:s', sprintf('%s %s:00', $sortedDate, $data['start_time']), $timezone)->setTimezone('UTC');
            $endTime = Carbon::createFromFormat('Y-m-d H:i:s', sprintf('%s %s:00', $sortedDate, $data['end_time']), $timezone)->setTimezone('UTC');

            $results[] = [
                'date' => $startTime->format('Y-m-d'),
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'employee_ids' => $employeeIDs
            ];
        }

        return $results;
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
                ], ResponseAlias::HTTP_FORBIDDEN);
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
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($overtime->approval_status != null) {
                return response()->json([
                    'status' => false,
                    'message' => "overtime already in approval"
                ], ResponseAlias::HTTP_BAD_REQUEST);
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

            // TODO: Sync with new flow for refresh finished status
            // $this->refreshFinishedStatus($overtime, $user->employee_id);

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

    public function checkIn(OvertimeCheckInRequest $request, int $id) {
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
                ->where('id', '=', $id)
                ->where('employee_id', '=', $user->employee_id)
                ->whereNull('check_in_time')
                ->first();
            if (!$employeeOvertime) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have any overtime need to check-in"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $employeeTimezone = getTimezoneV2($dataLocation['latitude'], $dataLocation['longitude']);

            $workLocation = $user->employee->getLastUnit();
            $overtimeRequest = $employeeOvertime->overtimeDate->overtime;
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

            // TODO: Sync with new flow
            // $this->refreshFinishedStatus($overtimeRequest, $user->employee_id);

            DB::commit();

            $this->getNotificationService()->createNotification(
                $user->employee_id,
                'Check In Lembur',
                'Check In Lembur Telah Berhasil'
            )->withSendPushNotification()->silent()->send();

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

    public function checkOut(OvertimeCheckOutRequest $request, int $id) {
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
                ->where('id', '=', $id)
                ->where('employee_id', '=', $user->employee_id)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->first();
            if (!$employeeOvertime) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have any overtime need to check-out"
                ], Response::HTTP_BAD_REQUEST);
            }

            /**
             * @var EmployeeAttendance $checkInData
             */
            $checkInData = $user->employee->attendances()
                ->where('attendance_types', '=', EmployeeAttendance::AttendanceTypeOvertime)
                ->orderBy('id', 'DESC')
                ->first();

            $employeeTimezone = getTimezoneV2($dataLocation['latitude'], $dataLocation['longitude']);
            $workLocation = $employeeOvertime->overtimeDate->overtime->unit;
            $overtimeRequest = $employeeOvertime->overtimeDate->overtime;
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

            // TODO: Sync with new flow
            // $this->refreshFinishedStatus($overtimeRequest, $user->employee_id);

            DB::commit();

            $this->getNotificationService()->createNotification(
                $user->employee_id,
                'Check Out Lembur',
                'Check Out Lembur Telah Berhasil'
            )->withSendPushNotification()->silent()->send();

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

    public function getActiveOvertime(Request $request, int $id) {
        /**
         * @var User $user
         */
        $user = $request->user();

        /**
         * @var OvertimeEmployee
         */
        $overtimeEmployee = OvertimeEmployee::query()
            ->with(['overtimeDate.overtime'])
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
            ->where(function(Builder $builder) {
                $builder->orWhereNull('overtime_employees.check_in_time')
                    ->orWhereNull('overtime_employees.check_out_time');
            })
            ->where('overtime_employees.employee_id', '=', $user->employee_id)
            ->where('overtimes.id', '=', $id)
            ->whereNotIn('overtimes.last_status', [OvertimeHistory::TypeRejected])
            ->orderBy('overtime_dates.start_time', 'ASC')
            ->select(['overtime_employees.*'])
            ->first();

        if (!$overtimeEmployee) {
            return response()->json([
                'status' => false,
                'message' => "You don't have any overtime date"
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => true,
            'message' => "Success",
            'data' => $overtimeEmployee
        ], ResponseAlias::HTTP_OK);
    }
}
