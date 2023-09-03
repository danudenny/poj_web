<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
use App\Http\Requests\EmployeeAttendance\ApprovalEmployeeAttendance;
use App\Http\Requests\EmployeeAttendance\CheckInAttendanceRequest;
use App\Http\Requests\EmployeeAttendance\CheckOutAttendanceRequest;
use App\Models\Approval;
use App\Models\ApprovalModule;
use App\Models\AttendanceApproval;
use App\Models\BackupEmployeeTime;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceHistory;
use App\Models\EmployeeTimesheetSchedule;
use App\Models\LateCheckin;
use App\Models\OvertimeEmployee;
use App\Models\Role;
use App\Models\UnitHasJob;
use App\Models\UnitJob;
use App\Models\User;
use App\Models\WorkReporting;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EmployeeAttendanceService extends BaseService
{
    private ApprovalService $approvalService;

    public function __construct(EmployeeTimesheetService $employeeTimesheetService, ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }
    public function index(Request $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        try {

            $unitRelationID = $request->get('unit_relation_id');

            $attendances = EmployeeAttendance::query();
            $attendances->with(['employee', 'employee.kanwil', 'employee.area', 'employee.cabang', 'employee.outlet', 'employee.employeeDetail', 'employee.employeeDetail.employeeTimesheet', 'employeeAttendanceHistory']);
            $attendances->join('employees', 'employees.id', '=', 'employee_attendances.employee_id');

            if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

            } else if ($this->isRequestedRoleLevel(Role::RoleAdminUnit)) {
                if (!$unitRelationID) {
                    $defaultUnitRelationID = $user->employee->unit_id;

                    if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                        $defaultUnitRelationID = $requestUnitRelationID;
                    }

                    $unitRelationID = $defaultUnitRelationID;
                }

            } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
                $attendances->leftJoin('user_operating_units', 'user_operating_units.unit_relation_id', '=', 'employees.default_operating_unit_id');
                $attendances->where(function (Builder $builder) use ($user) {
                    $builder->orWhere('user_operating_units.user_id', '=', $user->id);
                });
            } else {
                $subQuery = "(
                            WITH RECURSIVE job_data AS (
                                SELECT * FROM unit_has_jobs
                                WHERE unit_relation_id = '{$user->employee->unit_id}' AND odoo_job_id = {$user->employee->job_id}
                                UNION ALL
                                SELECT uj.* FROM unit_has_jobs uj
                                INNER JOIN job_data jd ON jd.id = uj.parent_unit_job_id
                            )
                            SELECT * FROM job_data
                        ) relatedJob";
                $attendances->join(DB::raw($subQuery), function (JoinClause $joinClause) {
                    $joinClause->on(DB::raw("relatedJob.odoo_job_id"), '=', DB::raw('employees.job_id'))
                        ->where(DB::raw("relatedJob.unit_relation_id"), '=', DB::raw('employees.unit_id'));
                });

                $attendances->where(function (Builder $builder) use ($user) {
                    $builder->orWhere(function(Builder $builder) use ($user) {
                        $builder->where('employees.job_id', '=', $user->employee->job_id)
                            ->where('employees.unit_id', '=', $user->employee->unit_id)
                            ->where('employees.id', '=', $user->employee_id);
                    })->orWhere(function (Builder $builder) use ($user) {
                        $builder->orWhere('employees.job_id', '!=', $user->employee->job_id)
                            ->orWhere('employees.unit_id', '!=', $user->employee->unit_id);
                    });
                });
            }

            if ($unitRelationID) {
                $attendances->where(function (Builder $builder) use ($unitRelationID) {
                    $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                        $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                            ->orWhere('employees.cabang_id', '=', $unitRelationID)
                            ->orWhere('employees.area_id', '=', $unitRelationID)
                            ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                            ->orWhere('employees.corporate_id', '=', $unitRelationID);
                    });
                });
            }

            $attendances->when($request->name, function ($query) use ($request) {
                $query->whereHas('employee', function (Builder $query) use ($request) {
                    $query->whereRaw('LOWER(name) LIKE ?', [strtolower('%' . request()->query('name') . '%')]);
                });
            });

            $clientTimezone = $this->getClientTimezone();

            if ($checkInDate = $request->get('check_in_date')) {
                $attendances->whereRaw("(employee_attendances.real_check_in::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone')::DATE = '$checkInDate'::DATE");
            }

            if ($checkOutDate = $request->get('check_out_date')) {
                $attendances->whereRaw("(employee_attendances.real_check_out::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone')::DATE = '$checkOutDate'::DATE");
            }

            $attendances->groupBy('employee_attendances.id')
                ->select(['employee_attendances.*'])
                ->orderBy('employee_attendances.id', 'DESC');

            return response()->json([
                'status' => true,
                'message' => 'Success get data!',
                'data' => $this->list($attendances, $request)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => self::SOMETHING_WRONG . ' : ' . $e->getMessage()
            ], 500);
        }
    }

    public function view(Request $request, int $id)
    {
        $attendance = EmployeeAttendance::query()->with(['employeeAttendanceHistory', 'employee', 'employee.job', 'attendanceApprovals', 'attendanceApprovals.employee', 'attendanceApprovals.employee'])
            ->where('id', '=', $id)->first();

        if (!$attendance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee attendance not found!'
            ], 400);
        }

        $attendance->append('is_can_approve');

        return response()->json([
            'status' => true,
            'message' => 'Success get data!',
            'data' => $attendance
        ]);
    }

    public function getListApproval(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $unitRelationID = $request->get('unit_relation_id');

        $query = AttendanceApproval::query()->with(['employeeAttendance', 'employeeAttendance.employee', 'employee']);
        $query->join('employee_attendances', 'employee_attendances.id', '=', 'attendance_approvals.employee_attendance_id');
        $query->join('employees AS reqEmployee', 'reqEmployee.id', '=', 'attendance_approvals.employee_id');
        $query->join('employees AS approvalEmployee', 'approvalEmployee.id', '=', 'employee_attendances.employee_id');

        if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

        } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
            if (!$unitRelationID) {
                $defaultUnitRelationID = $user->employee->unit_id;

                if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                    $defaultUnitRelationID = $requestUnitRelationID;
                }

                $unitRelationID = $defaultUnitRelationID;
            }
        } else {
            $query->where('attendance_approvals.employee_id', '=', $user->employee_id);
        }

        if ($unitRelationID) {
            $query->where(function (Builder $builder) use ($unitRelationID) {
                $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('reqEmployee.outlet_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.cabang_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.area_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.kanwil_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.corporate_id', '=', $unitRelationID);
                })->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('approvalEmployee.outlet_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.cabang_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.area_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.kanwil_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.corporate_id', '=', $unitRelationID);
                });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('attendance_approvals.status', '=', $status);
        }

        if ($startTime = $request->query('start_time')) {
            $query->where('attendance_approvals.created_at', '>=', $startTime);
        }

        if ($endTime = $request->query('end_time')) {
            $query->where('attendance_approvals.created_at', '<=', $endTime);
        }

        $query->select(['attendance_approvals.*']);
        $query->groupBy('attendance_approvals.id');
        $query->orderBy('attendance_approvals.id', 'DESC');

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $this->list($query, $request),
        ]);
    }

    function getLastUnit($data)
    {
        $lastData = null;
        foreach ($data as $item) {
            if ($item === null) {
                break;
            }
            $lastData = $item;
        }
        return $lastData;
    }
    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function checkIn($request): JsonResponse
    {
        $empData = auth()->user()->employee;
        $filteredUnitData = [$empData->kanwil, $empData->area, $empData->cabang, $empData->outlet];

        $timesheetSchedules = $empData->timesheetSchedules;
        if (!$timesheetSchedules) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee schedule not found!'
            ], 400);
        }

        $now = Carbon::now();
        $empSchedule = null;

        foreach ($timesheetSchedules as $schedule) {
            $day = $schedule['date'];
            $year = $schedule['period']['year'];
            $month = $schedule['period']['month'];
            $currentDate = Carbon::createFromDate($year, $month, $day);
            $carbonDate = $currentDate->format('Y-m-d');
            if ($carbonDate === $now->format('Y-m-d')) {
                $empSchedule = $schedule;
            }
        }

        if (!$empSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee schedule not found! Contact Administrator for Help!'
            ], 400);
        }

        $workLocation = \auth()->user()->employee->last_unit;

        // BEGIN : Check if time is in range
        $employeeTimeZone = getTimezone($request->lat, $request->long);
        if (!$workLocation->lat && !$workLocation->long) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work location not found!'
            ], 400);
        }
        $companyTimeZone = getTimezone(floatval($workLocation->lat), floatval($workLocation->long));

        $employeeTimesheetStartTime = Carbon::parse($empSchedule['timesheet']['start_time'], $companyTimeZone);
        $employeeTimesheetEndTime = Carbon::parse($empSchedule['timesheet']['end_time'], $companyTimeZone);
        if ($employeeTimesheetEndTime < $employeeTimesheetStartTime) {
            Carbon::parse($employeeTimesheetEndTime)->addDay();
        }
        $parseRequestedTime = Carbon::parse($now);
        $requestedTime = Carbon::createFromTimeString($parseRequestedTime);

        $adjustedStartTime = $employeeTimesheetStartTime->copy()->subMinutes($workLocation->early_buffer)->setTimezone($companyTimeZone);
        $adjustedEndTime = $employeeTimesheetEndTime->copy()->subMinutes($workLocation->late_buffer)->setTimezone($companyTimeZone);

        // END : Check if time is in range

        // BEGIN : Check if employee has checked in today
        $checkInData = $empData->attendances;

        foreach ($checkInData as $checkIn) {
            if ($checkIn->real_check_in) {
                if (Carbon::parse($checkIn->real_check_in)->format('Y-m-d') == Carbon::parse($parseRequestedTime)->format('Y-m-d')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You have checked in today!'
                    ], 400);
                }
            }
        }
        // END : Check if employee has checked in today

        // BEGIN : Calculate distance
        if ($workLocation->lat === null && $workLocation->long === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work location not found!'
            ], 400);
        }

        $distance = calculateDistance($request->lat, $request->long, floatval($workLocation->lat), floatval($workLocation->long));
        // END : Calculate distance

        $earlyTolerance = $workLocation->early_buffer;
        $lateTolerance = $workLocation->late_buffer;

        $employeeCheckInTime = Carbon::parse($now, $companyTimeZone);
        $companyCheckInTime = Carbon::createFromFormat('H:i', $empSchedule['timesheet']['start_time'], $companyTimeZone);

        $earlyBoundary = $companyCheckInTime->copy()->subMinutes($earlyTolerance);
        $lateBoundary = $companyCheckInTime->copy()->addMinutes($lateTolerance);

        $lateDifference = $lateBoundary->diffInMinutes($employeeCheckInTime);
        $isOnTime = $employeeCheckInTime->between($earlyBoundary, $lateBoundary);

        $isNeedApproval = false;
        if ($distance <= intval($workLocation->radius)) {
            $attType = "onsite";
        } else {
            $attType = "offsite";
            $isNeedApproval = true;
        }

        DB::beginTransaction();
        try {
            $checkIn = new EmployeeAttendance();
            $checkIn->employee_id = auth()->user()->employee_id;
            $checkIn->real_check_in = Carbon::now($companyTimeZone)->toDateTimeString();
            $checkIn->checkin_type = $attType;
            $checkIn->checkin_lat = $request->lat;
            $checkIn->checkin_long = $request->long;
            $checkIn->is_need_approval = $isNeedApproval;
            $checkIn->attendance_types = $request->attendance_types;
            $checkIn->checkin_real_radius = $distance;
            $checkIn->approved = !$isNeedApproval;
            $checkIn->check_in_tz = $employeeTimeZone;
            $checkIn->is_late = $lateDifference > 0;
            $checkIn->late_duration = $lateDifference;

            if (!$checkIn->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save data!'
                ], 400);
            }

            $attHistory = new EmployeeAttendanceHistory();
            $attHistory->employee_id = auth()->user()->employee_id;
            $attHistory->employee_attendances_id = $checkIn->id;
            $attHistory->status = !$isNeedApproval ? 'approved' : 'pending';

            if (!$attHistory->save()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save data!'
                ], 400);
            }

            $columnName = null;
            if ($lateDifference >= 15) {
                if ($lateDifference <= 15) {
                    $columnName = 'late_15';
                } else if ($lateDifference <= 30) {
                    $columnName = 'late_30';
                } else if ($lateDifference <= 45) {
                    $columnName = 'late_45';
                } else if ($lateDifference <= 60) {
                    $columnName = 'late_60';
                } else if ($lateDifference <= 75) {
                    $columnName = 'late_75';
                } else if ($lateDifference <= 90) {
                    $columnName = 'late_90';
                } else if ($lateDifference <= 105) {
                    $columnName = 'late_105';
                } else if ($lateDifference <= 120) {
                    $columnName = 'late_120';
                } else {
                    $columnName = 'late_120';
                }
                $year = $employeeCheckInTime->year;
                $month = $employeeCheckInTime->month;

                if ($columnName) {
                    $totalLateColumn = 'total_' . $columnName;
                    $lateCheckin = LateCheckin::where('employee_id', auth()->user()->employee_id)
                        ->where('year', $year)
                        ->where('month', $month)
                        ->first();

                    if (!$lateCheckin) {
                        $lateCheckin = new LateCheckin();
                        $lateCheckin->employee_id = auth()->user()->employee_id;
                        $lateCheckin->month = $month;
                        $lateCheckin->year = $year;
                        $lateCheckin->$columnName = 1;
                        $lateCheckin->$totalLateColumn = $lateDifference;
                        $lateCheckin->save();
                    }

                    $lateCheckin->$columnName++;
                    $lateCheckin->$totalLateColumn = $lateCheckin->$totalLateColumn + $lateDifference;
                    $lateCheckin->save();
                }
            }

            $getUser = User::where('employee_id', auth()->user()->employee_id)->first();
            $getUser->is_normal_checkin = true;
            $getUser->is_normal_checkout = false;
            $getUser->save();

            if ($isNeedApproval) {
                $getApproval = Approval::with(['users', 'approvalModule'])
                    ->whereHas('approvalModule', function ($query) {
                        $query->where('name', 'Attendance');
                    });
            }

            DB::commit();

            $notification = [
                'title' => 'Attendance Check In',
                'body' => 'You have successfully checked in!'
            ];

            $recipients = User::where('employee_id', auth()->user()->employee_id)->pluck('fcm_token')->toArray();

            fcm()
                ->to($recipients)
                ->priority('high')
                ->timeToLive(0)
                ->notification($notification)
                ->enableResponseLog()
                ->send();

            return response()->json([
                'status' => true,
                'message' => 'Success check in!',
                'data' => $checkIn
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function checkInV2(CheckInAttendanceRequest $request, int $id): JsonResponse
    {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
             */
            $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()
                ->where('employee_id', '=', $user->employee_id)
                ->where('id', '=', $id)
                ->whereNull('check_in_time')
                ->first();
            if (!$employeeTimesheetSchedule) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have any normal schedule need to check-in"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $dataLocation = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude')
            ];
            $employeeTimezone = getTimezoneV2(floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));

            $currentTime = Carbon::now();
            $checkInTime = Carbon::parse($employeeTimesheetSchedule->start_time);
            $minimumCheckInTime = Carbon::parse($employeeTimesheetSchedule->start_time)->addMinutes(-$employeeTimesheetSchedule->early_buffer);
            $maximumCheckInTime = Carbon::parse($employeeTimesheetSchedule->start_time)->addMinutes($employeeTimesheetSchedule->late_buffer);

            $lateDuration = 0;
            $attendanceStatus = "On Time";
            if ($currentTime->lessThan($minimumCheckInTime)) {
                return response()->json([
                    'status' => false,
                    'message' => sprintf("Minimum check in at %s", $minimumCheckInTime->setTimezone($employeeTimezone)->format('H:i:s'))
                ], ResponseAlias::HTTP_BAD_REQUEST);
            } else if ($currentTime->greaterThanOrEqualTo($minimumCheckInTime) && $currentTime->lessThan($checkInTime)) {
                $attendanceStatus = "Early Check In";
            } else if ($currentTime->greaterThan($maximumCheckInTime)) {
                $attendanceStatus = "Late";
                $lateDuration = $currentTime->diffInMinutes($maximumCheckInTime);
            }

            $earlyDuration = 0;
            if ($currentTime->lessThan($minimumCheckInTime)) {
                $earlyDuration = $minimumCheckInTime->diffInMinutes($currentTime);
            }

            $distance = calculateDistance($employeeTimesheetSchedule->latitude, $employeeTimesheetSchedule->longitude, floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));

            $attendanceType = EmployeeAttendance::TypeOnSite;
            $isNeedApproval = false;
            if ($distance > intval($employeeTimesheetSchedule->timesheet->unit->radius)) {
                $attendanceType = EmployeeAttendance::TypeOffSite;
                $isNeedApproval = true;
            }

            $approvalEmployeeIDs = [];
            $approvalType = null;
            if ($isNeedApproval && $attendanceType == EmployeeAttendance::TypeOffSite) {
                $approvalType = AttendanceApproval::TypeOffsite;
                $approvalUsers = $this->approvalService->getApprovalUser($user->employee, ApprovalModule::ApprovalOffsiteAttendance);
                foreach ($approvalUsers as $approvalUser) {
                    $approvalEmployeeIDs[] = $approvalUser->employee_id;
                }

                if (count($approvalEmployeeIDs) == 0) {
                    $isNeedApproval = false;
                }
            }

            DB::beginTransaction();

            $checkIn = new EmployeeAttendance();
            $checkIn->employee_id = auth()->user()->employee_id;
            $checkIn->real_check_in = $currentTime->format('Y-m-d H:i:s');
            $checkIn->checkin_type = $attendanceType;
            $checkIn->checkin_lat = $dataLocation['latitude'];
            $checkIn->checkin_long = $dataLocation['longitude'];
            $checkIn->is_need_approval = $isNeedApproval;
            $checkIn->attendance_types = EmployeeAttendance::AttendanceTypeNormal;
            $checkIn->checkin_real_radius = $distance;
            $checkIn->approved = !$isNeedApproval;
            $checkIn->check_in_tz = $employeeTimezone;
            $checkIn->is_late = $lateDuration > 0;
            $checkIn->late_duration = $lateDuration;
            $checkIn->early_duration = $earlyDuration;
            $checkIn->save();

            $attendanceHistory = new EmployeeAttendanceHistory();
            $attendanceHistory->employee_id = $user->employee_id;
            $attendanceHistory->employee_attendances_id = $checkIn->id;
            $attendanceHistory->status = !$isNeedApproval ? 'approved' : 'pending';
            $attendanceHistory->save();

            $employeeTimesheetSchedule->check_in_time = $currentTime;
            $employeeTimesheetSchedule->check_in_latitude = $dataLocation['latitude'];
            $employeeTimesheetSchedule->check_in_longitude = $dataLocation['longitude'];
            $employeeTimesheetSchedule->check_in_timezone = $employeeTimezone;
            $employeeTimesheetSchedule->employee_attendance_id = $checkIn->id;
            $employeeTimesheetSchedule->save();

            foreach ($approvalEmployeeIDs as $idx => $approvalEmployeeID) {
                $attendanceApproval = new AttendanceApproval();
                $attendanceApproval->priority = $idx;
                $attendanceApproval->approval_type = $approvalType;
                $attendanceApproval->employee_attendance_id = $checkIn->id;
                $attendanceApproval->employee_id = $approvalEmployeeID;
                $attendanceApproval->status = AttendanceApproval::StatusPending;
                $attendanceApproval->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success check in!',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function checkOutV2(CheckOutAttendanceRequest $request, int $id)
    {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
             */
            $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()
                ->where('employee_id', '=', $user->employee_id)
                ->where('id', '=', $id)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->first();
            if (!$employeeTimesheetSchedule) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have any normal schedule need to check-out"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $dataLocation = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude')
            ];

            $employeeAttendance = $employeeTimesheetSchedule->employeeAttendance;
            if ($employeeAttendance == null) {
                return response()->json([
                    'status' => false,
                    'message' => "Employee attendance is not found"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $employeeTimezone = getTimezoneV2(floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));
            $currentTime = Carbon::now();
            $distance = calculateDistance($employeeTimesheetSchedule->latitude, $employeeTimesheetSchedule->longitude, floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));

            $attendanceType = EmployeeAttendance::TypeOnSite;
            if ($distance > intval($employeeTimesheetSchedule->timesheet->unit->radius)) {
                $attendanceType = EmployeeAttendance::TypeOffSite;
            }

            $checkOutTime = Carbon::parse($employeeTimesheetSchedule->end_time, 'UTC');

            $earlyDuration = 0;
            if ($currentTime->lessThan($checkOutTime)) {
                $earlyDuration = $checkOutTime->diffInMinutes($currentTime);
            }

            DB::beginTransaction();

            $employeeAttendance->real_check_out = $currentTime;
            $employeeAttendance->checkout_lat = $dataLocation['latitude'];
            $employeeAttendance->checkout_long = $dataLocation['longitude'];
            $employeeAttendance->checkout_real_radius = $distance;
            $employeeAttendance->check_out_tz = $employeeTimezone;
            $employeeAttendance->checkout_type = $attendanceType;
            $employeeAttendance->early_check_out = $earlyDuration;
            $employeeAttendance->save();

            $attHistory = new EmployeeAttendanceHistory();
            $attHistory->employee_id = $user->employee_id;
            $attHistory->employee_attendances_id = $employeeAttendance->id;
            $attHistory->status = $employeeAttendance->approved ? 'approved' : 'pending';
            $attHistory->save();

            $employeeTimesheetSchedule->check_out_time = $currentTime;
            $employeeTimesheetSchedule->check_out_latitude = $dataLocation['latitude'];
            $employeeTimesheetSchedule->check_in_longitude = $dataLocation['longitude'];
            $employeeTimesheetSchedule->check_out_timezone = $employeeTimezone;
            $employeeTimesheetSchedule->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success check out!',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @throws GuzzleException
     */
    public function checkOut($request): JsonResponse
    {
        $empData = auth()->user()->employee;

        $filteredUnitData = [$empData->corporate, $empData->kanwil, $empData->area, $empData->cabang, $empData->outlet];
        $timesheetSchedules = $empData->timesheetSchedules;

        if (!$timesheetSchedules) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee schedule not found!'
            ], 400);
        }

        $now = Carbon::now();
        $empSchedule = null;

        foreach ($timesheetSchedules as $schedule) {
            $day = $schedule['date'];
            $year = $schedule['period']['year'];
            $month = $schedule['period']['month'];
            $carbonDate = Carbon::create($year, $month, $day)->format('Y-m-d');

            if ($carbonDate === $now->format('Y-m-d')) {
                $empSchedule = $schedule;
            }
        }

        if (!$empSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee schedule not found! Contact Administrator for Help!'
            ], 400);
        }

        $workLocation = $this->getLastUnit($filteredUnitData);

        // BEGIN : Check if time is in range
        $employeeTimeZone = getTimezone($request->lat, $request->long);
        $companyTimeZone = getTimezone(floatval($workLocation->lat), floatval($workLocation->long));

        $employeeTimesheetStartTime = Carbon::parse($empSchedule['timesheet']['start_time'], $companyTimeZone);
        $employeeTimesheetEndTime = Carbon::parse($empSchedule['timesheet']['end_time'], $companyTimeZone);
        if ($employeeTimesheetEndTime < $employeeTimesheetStartTime) {
            Carbon::parse($employeeTimesheetEndTime)->addDay();
        }
        $parseRequestedTime = Carbon::parse($now);
        $requestedTime = Carbon::createFromTimeString($parseRequestedTime);
        // END : Check if time is in range

        //check if employee has submit worklocation today
        $workReporting = WorkReporting::where('employee_id', $empData->id)
            ->whereDate('date', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        if (!$workReporting) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have not submit work reporting today!'
            ], 400);
        }

        $employeeTimeZone = getTimezone($request->lat, $request->long);
        $companyTimeZone = getTimezone(floatval($workLocation['lat']), floatval($workLocation['long']));
        $checkInData = $empData->attendances->first();

        if (!auth()->user()->is_normal_checkin) {
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
            $request->lat,
            $request->long,
            floatval($workLocation['lat']),
            floatval($workLocation['long'])
        );

        DB::beginTransaction();
        try {
            $checkInData->real_check_out = Carbon::now($companyTimeZone)->toDateTimeString();
            $checkInData->checkout_lat = $request->lat;
            $checkInData->checkout_long = $request->long;
            $checkInData->checkout_real_radius = $distance;
            $checkInData->check_out_tz = $employeeTimeZone;
            $checkInData->is_early = Carbon::parse(now(), $companyTimeZone)->lessThan(Carbon::parse($empSchedule['timesheet']['end_time'], $companyTimeZone));
            if ($requestedTime->lessThan($employeeTimesheetEndTime)) {
                $checkInData->early_duration = $requestedTime->diffInMinutes($employeeTimesheetEndTime);
            } else {
                $checkInData->early_duration = 0;
            }

            $checkInData->duration = Carbon::parse($checkInData->real_check_in, $companyTimeZone)->diffInMinutes(Carbon::parse($checkInData->real_check_out, $companyTimeZone));
            if (!$checkInData->save()) {
                throw new Exception('Failed save checkout data!');
            }

            $attHistory = new EmployeeAttendanceHistory();
            $attHistory->employee_id = auth()->user()->employee_id;
            $attHistory->employee_attendances_id = $checkInData->id;
            $attHistory->status = $checkInData->approved ? 'approved' : 'pending';

            if (!$attHistory->save()) {
                throw new Exception('Failed save attendance histories data!');
            }

            $getUser = User::where('employee_id', auth()->user()->employee_id)->first();
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

    public function approve(ApprovalEmployeeAttendance $request, $id): JsonResponse
    {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var EmployeeAttendance $employeeAttendance
             */
            $employeeAttendance = EmployeeAttendance::query()->where('id', '=', $id)->first();
            if (!$employeeAttendance) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee attendance not found!'
                ], ResponseAlias::HTTP_NOT_FOUND);
            }

            /**
             * @var AttendanceApproval $attendanceApproval
             */
            $attendanceApproval = $employeeAttendance->attendanceApprovals()
                ->where('employee_id', '=', $user->employee_id)
                ->where('status', AttendanceApproval::StatusPending)
                ->first();
            if(!$attendanceApproval) {
                return response()->json([
                    'status' => false,
                    'message' => 'You don\'t have access to do approval!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($attendanceApproval->priority > 0) {
                $isPendingBeforeExist = $employeeAttendance->attendanceApprovals()
                    ->where('priority', '<', $attendanceApproval->priority)
                    ->where('status', '=', AttendanceApproval::StatusPending)
                    ->exists();
                if ($isPendingBeforeExist) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Waiting last approver to do approval!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            DB::beginTransaction();

            $attendanceApproval->status = $request->input('status');
            $attendanceApproval->notes = $request->input('notes');
            $attendanceApproval->save();

            if ($attendanceApproval->status == AttendanceApproval::StatusApproved) {
                $isNextExist = $employeeAttendance->attendanceApprovals()
                    ->where('priority', '>', $attendanceApproval->priority)
                    ->where('status', '=', AttendanceApproval::StatusPending)
                    ->exists();
                if (!$isNextExist) {
                    $employeeAttendance->is_need_approval = false;
                    $employeeAttendance->approved = true;
                    $employeeAttendance->save();

                    $this->getNotificationService()->createNotification(
                        $employeeAttendance->employee_id,
                        'Your attendance has been approved',
                        ''
                    )->withSendPushNotification()->silent()->send();
                }
            } else if ($attendanceApproval->status == AttendanceApproval::StatusRejected){
                /**
                 * @var AttendanceApproval[] $nextApprovals
                 */
                $nextApprovals = $employeeAttendance->attendanceApprovals()
                    ->where('priority', '>', $attendanceApproval->priority)
                    ->get();
                foreach ($nextApprovals as $nextApproval) {
                    $nextApproval->status = AttendanceApproval::StatusRejected;
                    $nextApproval->save();
                }

                $employeeAttendance->is_need_approval = false;
                $employeeAttendance->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success save data!'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getActiveAttendance(Request $request)
    {
        try {
            /**
             * @var Employee $employee
             */
            $employee = $request->user()->employee;

            $metaData = [
                'employee_id' => $employee->id,
                'system_current_time' => Carbon::now()->format('Y-m-d H:i:s'),
                'current_time_with_timezone' => null,
                'timezone' => null,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ];

            if ($metaData['latitude'] == null || $metaData['longitude'] == null) {
                return response()->json([
                    'status' => false,
                    'message' => "Invalid Location"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $timezone = $this->getClientTimezone();

            $metaData['current_time_with_timezone'] = Carbon::now()->setTimezone($timezone)->format('Y-m-d H:i:s');
            $metaData['timezone'] = $timezone;

            $activeSchedule = [
                'current_attendance' => null,
                'attendance' => [
                    'normal' => null,
                    'event' => null,
                    'overtime' => null,
                    'backup' => null,
                ],
                'meta_data' => $metaData
            ];

            $parsedCurrentTimeWithTimezone = Carbon::parse($metaData['current_time_with_timezone']);

            if ($event = $employee->getActiveEvent()) {
                $activeSchedule['attendance']['event'] = [
                    'start_time' => Carbon::parse($event->event_datetime)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'check_in_time' => $event->check_in_time ? Carbon::parse($event->check_in_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'check_out_time' => $event->check_out_time ? Carbon::parse($event->check_out_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'reference_id' => $event->id,
                    'total_reporting' => 0
                ];
            }

            if ($overtime = $employee->getActiveOvertime($timezone)) {
                $unit = $overtime->overtimeDate->overtime->unit;

                $jobs = $unit->jobs;
                $listJobs = [];

                foreach ($jobs as $job) {
                    if ($job->odoo_job_id === $employee->job_id) {
                        $listJobs[] = [
                            'is_camera' => $job->pivot->is_camera,
                            'is_upload' => $job->pivot->is_upload,
                            'is_reporting' => $job->pivot->is_mandatory_reporting,
                            'total_reporting' => $job->pivot->total_normal,
                            'total_normal' => $job->pivot->total_normal,
                            'total_backup' => $job->pivot->total_backup,
                            'total_overtime' => $job->pivot->total_overtime,
                        ];
                    }
                }

                $activeSchedule['attendance']['overtime'] = [
                    'start_time' => Carbon::parse($overtime->overtimeDate->start_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'end_time' => Carbon::parse($overtime->overtimeDate->end_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'check_in_time' => $overtime->check_in_time ? Carbon::parse($overtime->check_in_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'check_out_time' => $overtime->check_out_time ? Carbon::parse($overtime->check_out_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'reference_type' => 'overtime',
                    'reference_id' => $overtime->id,
                    'total_reporting' => WorkReporting::query()
                        ->where('reference_type', '=', WorkReporting::TypeOvertime)
                        ->where('reference_id', '=', $overtime->id)
                        ->count(),
                    'unit_target' => [
                        'name' => $unit->name,
                        'latitude' => $unit->lat,
                        'longitude' => $unit->long,
                        'radius' => $unit->radius,
                    ],
                    'work_reporting' => $listJobs
                ];

                $activeSchedule['current_attendance'] = $activeSchedule['attendance']['overtime'];
            }

            if ($backup = $employee->getActiveBackup($timezone)) {
                $unit = $backup->backupTime->backup->unit;
                $backupJob = $backup->backupTime->backup->job;

                $jobs = $unit->jobs;
                $listJobs = [];

                foreach ($jobs as $job) {
                    if ($job->odoo_job_id === $backupJob->odoo_job_id) {
                        $listJobs[] = [
                            'is_camera' => $job->pivot->is_camera,
                            'is_upload' => $job->pivot->is_upload,
                            'is_reporting' => $job->pivot->is_mandatory_reporting,
                            'total_reporting' => $job->pivot->total_normal,
                            'total_normal' => $job->pivot->total_normal,
                            'total_backup' => $job->pivot->total_backup,
                            'total_overtime' => $job->pivot->total_overtime,
                        ];
                    }
                }

                $activeSchedule['attendance']['backup'] = [
                    'start_time' => Carbon::parse($backup->backupTime->start_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'end_time' => Carbon::parse($backup->backupTime->end_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'check_in_time' => $backup->check_in_time ? Carbon::parse($backup->check_in_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'check_out_time' => $backup->check_out_time ? Carbon::parse($backup->check_out_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'reference_type' => 'backup',
                    'reference_id' => $backup->id,
                    'total_reporting' => WorkReporting::query()
                    ->where('reference_type', '=', WorkReporting::TypeBackup)
                    ->where('reference_id', '=', $backup->id)
                    ->count(),
                    'unit_target' => [
                        'name' => $unit->name,
                        'latitude' => $unit->lat,
                        'longitude' => $unit->long,
                        'radius' => $unit->radius,
                    ],
                    'work_reporting' => $listJobs
                ];

                if (is_null($activeSchedule['current_attendance'])) {
                    $activeSchedule['current_attendance'] = $activeSchedule['attendance']['backup'];
                } else {
                    if (Carbon::parse($metaData['current_time_with_timezone'])->between(Carbon::parse($activeSchedule['attendance']['backup']['start_time']), Carbon::parse($activeSchedule['attendance']['backup']['end_time']))) {
                        $activeSchedule['current_attendance'] = $activeSchedule['attendance']['backup'];
                    }
                    if (
                        Carbon::parse($activeSchedule['attendance']['backup']['start_time'])->lessThan($activeSchedule['current_attendance']['start_time']) &&
                        $activeSchedule['attendance']['backup']['check_in_time'] != null
                    ) {
                        $activeSchedule['current_attendance'] = $activeSchedule['attendance']['backup'];
                    }
                }
            }

            if ($normal = $employee->getActiveNormalSchedule($timezone)) {
                $unit = $normal->unit;

                $jobs = $unit->jobs;
                $listJobs = [];

                foreach ($jobs as $job) {
                    if ($job->odoo_job_id === $employee->job_id) {
                        $listJobs[] = [
                            'is_camera' => $job->pivot->is_camera,
                            'is_upload' => $job->pivot->is_upload,
                            'is_reporting' => $job->pivot->is_mandatory_reporting,
                            'total_reporting' => $job->pivot->total_normal,
                            'total_normal' => $job->pivot->total_normal,
                            'total_backup' => $job->pivot->total_backup,
                            'total_overtime' => $job->pivot->total_overtime,
                        ];
                    }
                }

                $activeSchedule['attendance']['normal'] = [
                    'minimum_start_time' => Carbon::parse($normal->start_time)->addMinutes(-$normal->early_buffer)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'start_time' => Carbon::parse($normal->start_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'maximum_start_time' => Carbon::parse($normal->start_time)->addMinutes($normal->late_buffer)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'end_time' => Carbon::parse($normal->end_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'check_in_time' => $normal->check_in_time ? Carbon::parse($normal->check_in_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'check_out_time' => $normal->check_out_time ? Carbon::parse($normal->check_out_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'early_buffer' => $normal->early_buffer,
                    'late_buffer' => $normal->late_buffer,
                    'timesheet_name' => $normal->timesheet->name,
                    'reference_type' => 'normal',
                    'reference_id' => $normal->id,
                    'total_reporting' => WorkReporting::query()
                        ->where('reference_type', '=', WorkReporting::TypeNormal)
                        ->where('reference_id', '=', $normal->id)
                        ->count(),
                    'unit_target' => [
                        'name' => $unit->name,
                        'latitude' => $unit->lat,
                        'longitude' => $unit->long,
                        'radius' => $unit->radius,
                    ],
                    'work_reporting' => $listJobs
                ];

                if (is_null($activeSchedule['current_attendance'])) {
                    $activeSchedule['current_attendance'] = $activeSchedule['attendance']['normal'];
                } else {
                    if (Carbon::parse($metaData['current_time_with_timezone'])->between(Carbon::parse($activeSchedule['attendance']['normal']['start_time']), Carbon::parse($activeSchedule['attendance']['normal']['end_time']))) {
                        $activeSchedule['current_attendance'] = $activeSchedule['attendance']['normal'];
                    }
                    if (
                        Carbon::parse($activeSchedule['attendance']['normal']['start_time'])->greaterThan($activeSchedule['current_attendance']['end_time']) &&
                        $parsedCurrentTimeWithTimezone->greaterThanOrEqualTo(Carbon::parse($activeSchedule['attendance']['normal']['start_time']))
                    ) {
                        $activeSchedule['current_attendance'] = $activeSchedule['attendance']['normal'];
                    }
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => $activeSchedule
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function monthEvaluate(Request $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();
            $clientTimezone = getClientTimezone();

            $query = EmployeeTimesheetSchedule::query()->with(['unit', 'employeeAttendance'])
                ->where('employee_timesheet_schedules.employee_id', '=', $user->employee_id);

            if ($monthly = $request->query('monthly')) {
                $query->whereRaw("TO_CHAR((employee_timesheet_schedules.start_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone'), 'YYYY-mm') = ?", [$monthly]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Succcess fetch data',
                'data' => [
                    'meta' => [
                        'full_attendance' => (clone $query)->whereNotNull('employee_timesheet_schedules.check_in_time')->whereNotNull('employee_timesheet_schedules.check_out_time')->count(),
                        'late_check_in' => (clone $query)->whereRaw('employee_timesheet_schedules.check_in_time > employee_timesheet_schedules.start_time')->count(),
                        'not_check_out' => (clone $query)->whereNull('employee_timesheet_schedules.check_out_time')->count(),
                        'early_check_out' => (clone $query)->whereRaw('employee_timesheet_schedules.check_out_time < employee_timesheet_schedules.end_time')->count(),
                        'not_attendance' => (clone $query)->whereNull('employee_timesheet_schedules.check_in_time')->whereNull('employee_timesheet_schedules.check_out_time')->count(),
                        'total_schedule' => (clone $query)->count(),
                    ],
                    'data' => $this->list($query, $request)
                ],
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAllSchedules(Request $request) {
        /**
         * @var User  $user
         */
        $user = $request->user();

        $clientTimezone = $this->getClientTimezone();
        $preQuery = EmployeeTimesheetSchedule::query()->selectRaw("
                    'normal' AS reference_type,
                    employee_timesheet_schedules.id AS reference_id,
                    start_time AS start_time,
                    end_time AS end_time,
                    timezone AS timezone,
                    (start_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS start_time_with_timezone,
                    (end_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS end_time_with_timezone,
                    check_in_time AS check_in_time,
                    check_out_time AS check_out_time,
                    (check_in_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS check_in_time_with_timezone,
                    (check_out_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS check_out_time_with_timezone,
                    (u.name) AS unit_name
                ")->where('employee_id', '=', $user->employee_id)
                ->join('units AS u', 'u.relation_id', '=', 'employee_timesheet_schedules.unit_relation_id')
            ->unionAll(OvertimeEmployee::query()->selectRaw("
                    'overtime' AS reference_type,
                    overtime_employees.id AS reference_id,
                    od.start_time AS start_time,
                    od.end_time AS end_time,
                    o.timezone AS timezone,
                    (od.start_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS check_in_time_with_timezone,
                    (od.end_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS check_out_time_with_timezone,
                    overtime_employees.check_in_time AS check_in_time,
                    overtime_employees.check_out_time AS check_out_time,
                    (overtime_employees.check_in_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS check_in_time_with_timezone,
                    (overtime_employees.check_out_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS check_out_time_with_timezone,
                    (u.name) AS unit_name
                ")->join('overtime_dates AS od', 'overtime_employees.overtime_date_id', '=', 'od.id')
                ->join('overtimes AS o', 'o.id', '=', 'od.overtime_id')
                ->join('units AS u', 'u.relation_id', '=', 'o.unit_relation_id')
                ->where('employee_id', '=', $user->employee_id)
            )->unionAll(BackupEmployeeTime::query()->selectRaw("
                    'backup' AS reference_type,
                    backup_employee_times.id AS reference_id,
                    bt.start_time AS start_time,
                    bt.end_time AS end_time,
                    b.timezone AS timezone,
                    (bt.start_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS start_time_with_timezone,
                    (bt.end_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS end_time_with_timezone,
                    backup_employee_times.check_in_time AS check_in_time,
                    backup_employee_times.check_out_time AS check_out_time,
                    (backup_employee_times.check_in_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS check_in_time_with_timezone,
                    (backup_employee_times.check_out_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone') AS check_out_time_with_timezone,
                    (u.name) AS unit_name
                ")->join('backup_times AS bt', 'backup_employee_times.backup_time_id', '=', 'bt.id')
                ->join('backups AS b', 'b.id', '=', 'bt.backup_id')
                ->join('units AS u', 'u.relation_id', '=', 'b.unit_id')
                ->where('employee_id', '=', $user->employee_id)
            );

        $query = DB::table(DB::raw("({$preQuery->toSql()}) as d"))->select('*')->mergeBindings($preQuery->getQuery());

        if ($date = $request->input('date')) {
            $query->whereRaw("start_time_with_timezone::DATE = '$date'");
        }
        if ($reference_type = $request->query('reference_type')) {
            $query->whereRaw("reference_type = '$reference_type'");
        }

        if ($startTime = $request->query('start_time')) {
            $query->whereRaw("start_time_with_timezone::DATE >= '$startTime'");
        }
        if ($endTime = $request->query('end_time')) {
            $query->whereRaw("end_time_with_timezone <= '$endTime'");
        }

        $query->orderBy('start_time_with_timezone', 'ASC');

        return response()->json([
            'status' => true,
            'message' => 'success',
            'data' => $this->list($query, $request)
        ]);
    }
}
