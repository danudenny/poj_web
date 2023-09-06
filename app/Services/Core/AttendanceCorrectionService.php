<?php

namespace App\Services\Core;

use App\Http\Requests\AttendanceCorrection\ApprovalCorrectionRequest;
use App\Http\Requests\AttendanceCorrection\CreateCorrectionRequest;
use App\Models\ApprovalModule;
use App\Models\AttendanceCorrectionApproval;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BackupEmployeeTime;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeTimesheetSchedule;
use App\Models\OvertimeEmployee;
use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

class AttendanceCorrectionService extends BaseService
{
    private ApprovalService $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $query = AttendanceCorrectionRequest::query()->with(['employee'])
            ->orderBy('id', 'DESC');

        $requestorEmployeeID = null;

        if ($this->isRequestedRoleLevel(Role::RoleStaff)) {
            $requestorEmployeeID = $user->employee_id;
        }

        if ($requestorEmployeeID) {
            $query->where('employee_id', '=', $requestorEmployeeID);
        }
        if ($status = $request->query('status')) {
            $query->where('status', '=', $status);
        }
        if ($startTime = $request->query('start_time')) {
            $query->where('created_at', '>=', $startTime);
        }
        if ($endTime = $request->query('end_time')) {
            $query->where('created_at', '<=', $endTime);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $this->list($query, $request),
        ]);
    }

    public function view(Request $request, int $id) {
        /**
         * @var User $user
         */
        $user = $request->user();

        /**
         * @var AttendanceCorrectionRequest $attendanceCorrectionRequest
         */
        $attendanceCorrectionRequest = AttendanceCorrectionRequest::query()
            ->with(['employee', 'attendanceCorrectionApprovals'])
            ->where('id', '=', $id)
            ->first();
        if (!$attendanceCorrectionRequest) {
            return response()->json([
                'status' => false,
                'message' => 'Attendance correction request not found!'
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        $attendanceCorrectionRequest->append(['is_can_approve']);

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $attendanceCorrectionRequest,
        ]);
    }

    public function listApproval(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $query = AttendanceCorrectionApproval::query()->with(['attendanceCorrectionRequest', 'attendanceCorrectionRequest.employee'])
            ->where('employee_id', '=', $user->employee_id)
            ->orderBy('id', 'DESC');

        if ($status = $request->query('status')) {
            $query->where('status', '=', $status);
        }

        if ($startTime = $request->query('start_time')) {
            $query->where('created_at', '>=', $startTime);
        }
        if ($endTime = $request->query('end_time')) {
            $query->where('created_at', '<=', $endTime);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $this->list($query, $request),
        ]);
    }

    public function createRequest(CreateCorrectionRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $data = [
                'correction_date' => $request->input('correction_date'),
                'check_in_time' => $request->input('check_in_time'),
                'check_out_time' => $request->input('check_out_time'),
                'reference_type' => $request->input('reference_type'),
                'reference_id' => $request->input('reference_id'),
                'employee_attendance_id' => 0,
                'latitude' => null,
                'longitude' => null,
                'timezone' => null,
                'data_schedule' => null
            ];

            switch ($data['reference_type']) {
                case AttendanceCorrectionRequest::TypeNormal:
                    /**
                     * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
                     */
                    $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()
                        ->where('id', '=', $data['reference_id'])
                        ->where('employee_id', '=', $user->employee_id)
                        ->first();
                    if (!$employeeTimesheetSchedule) {
                        return response()->json([
                            'status' => false,
                            'message' => 'There is no employee timesheet schedule!'
                        ], ResponseAlias::HTTP_BAD_REQUEST);
                    }

                    $data['employee_attendance_id'] = $employeeTimesheetSchedule->employee_attendance_id;
                    $data['latitude'] = $employeeTimesheetSchedule->latitude;
                    $data['longitude'] = $employeeTimesheetSchedule->longitude;
                    $data['timezone'] = $employeeTimesheetSchedule->timezone;
                    $data['data_schedule'] = $employeeTimesheetSchedule;
                    break;
                case AttendanceCorrectionRequest::TypeOvertime:
                    /**
                     * @var OvertimeEmployee $overtimeEmployee
                     */
                    $overtimeEmployee = OvertimeEmployee::query()
                        ->where('id', '=', $data['reference_id'])
                        ->where('employee_id', '=', $user->employee_id)
                        ->first();
                    if (!$overtimeEmployee) {
                        return response()->json([
                            'status' => false,
                            'message' => 'There is no employee overtime!'
                        ], ResponseAlias::HTTP_BAD_REQUEST);
                    }

                    $data['employee_attendance_id'] = $overtimeEmployee->employee_attendance_id;
                    $data['latitude'] = $overtimeEmployee->overtimeDate->overtime->location_lat;
                    $data['longitude'] = $overtimeEmployee->overtimeDate->overtime->location_long;
                    $data['timezone'] = $overtimeEmployee->overtimeDate->overtime->timezone;
                    $data['data_schedule'] = $overtimeEmployee;
                    break;
                case AttendanceCorrectionRequest::TypeBackup:
                    /**
                     * @var BackupEmployeeTime $backupEmployeeTime
                     */
                    $backupEmployeeTime = BackupEmployeeTime::query()
                        ->where('id', '=', $data['reference_id'])
                        ->where('employee_id', '=', $user->employee_id)
                        ->first();
                    if (!$backupEmployeeTime) {
                        return response()->json([
                            'status' => false,
                            'message' => 'There is no backup employee time!'
                        ], ResponseAlias::HTTP_BAD_REQUEST);
                    }

                    $data['employee_attendance_id'] = $backupEmployeeTime->employee_attendance_id;
                    $data['latitude'] = $backupEmployeeTime->backupTime->backup->location_lat;
                    $data['longitude'] = $backupEmployeeTime->backupTime->backup->location_long;
                    $data['timezone'] = $backupEmployeeTime->backupTime->backup->timezone;
                    $data['data_schedule'] = $backupEmployeeTime;
                    break;
            }

            $approvalEmployeeIDs = [];
            $approvalUsers = $this->approvalService->getApprovalUser($user->employee, ApprovalModule::ApprovalAttendanceCorrection);
            foreach ($approvalUsers as $approvalUser) {
                $approvalEmployeeIDs[] = $approvalUser->employee_id;
            }

            DB::beginTransaction();

            /**
             * @var EmployeeAttendance $employeeAttendance
             */
            $employeeAttendance = EmployeeAttendance::query()
                ->where('id', '=', $data['employee_attendance_id'])
                ->first();
            if (!$employeeAttendance) {
                $checkInTime = Carbon::parse($data['correction_date'] . " " . $data['check_in_time'], $data['timezone'])->setTimezone('UTC');
                $checkOutTime = Carbon::parse($data['correction_date'] . " " . $data['check_out_time'], $data['timezone'])->setTimezone('UTC');

                if ($checkOutTime->isBefore($checkInTime)) {
                    $checkOutTime->addDays(1);
                }

                $employeeAttendance = new EmployeeAttendance();
                $employeeAttendance->employee_id = $user->employee_id;
                $employeeAttendance->checkin_type = EmployeeAttendance::TypeOnSite;
                $employeeAttendance->real_check_in = $checkInTime->format('Y-m-d H:i:s');
                $employeeAttendance->real_check_out = $checkOutTime->format('Y-m-d H:i:s');
                $employeeAttendance->checkin_lat = $data['latitude'];
                $employeeAttendance->checkin_long = $data['longitude'];
                $employeeAttendance->checkout_lat = $data['latitude'];
                $employeeAttendance->checkout_long = $data['longitude'];
                $employeeAttendance->check_in_tz = $data['timezone'];
                $employeeAttendance->check_out_tz = $data['timezone'];
                $employeeAttendance->attendance_types = $data['reference_type'];
                $employeeAttendance->checkin_real_radius = 0;
                $employeeAttendance->is_late = false;
                $employeeAttendance->late_duration = 0;
            }

            $employeeAttendance->is_need_approval = true;
            $employeeAttendance->approved = false;
            $employeeAttendance->notes = "waiting_attendance_correction";
            $employeeAttendance->save();

            if ($data['data_schedule']) {
                $data['data_schedule']->employee_attendance_id = $employeeAttendance->id;
                $data['data_schedule']->save();
            }

            if ($data['reference_id'] == null) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no attendance reference!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $attendanceCorrectionRequest = new AttendanceCorrectionRequest();
            $attendanceCorrectionRequest->correction_date = $data['correction_date'];
            $attendanceCorrectionRequest->correction_type = $request->input('correction_type');
            $attendanceCorrectionRequest->employee_id = $user->employee_id;
            $attendanceCorrectionRequest->employee_attendance_id = $employeeAttendance->id;
            $attendanceCorrectionRequest->reference_type = $data['reference_type'];
            $attendanceCorrectionRequest->reference_id = $data['reference_id'];
            $attendanceCorrectionRequest->status = AttendanceCorrectionApproval::StatusPending;
            $attendanceCorrectionRequest->check_in_time = $data['check_in_time'];
            $attendanceCorrectionRequest->check_out_time = $data['check_out_time'];
            $attendanceCorrectionRequest->notes = $request->input('notes');
            $attendanceCorrectionRequest->file_url = $request->input('file');

            if(count($approvalEmployeeIDs) == 0) {
                $attendanceCorrectionRequest->status = AttendanceCorrectionApproval::StatusApproved;
            }

            $attendanceCorrectionRequest->save();

            foreach ($approvalEmployeeIDs as $idx => $approvalEmployeeID) {
                $attendanceCorrectionApproval = new AttendanceCorrectionApproval();
                $attendanceCorrectionApproval->priority = $idx;
                $attendanceCorrectionApproval->attendance_correction_request_id = $attendanceCorrectionRequest->id;
                $attendanceCorrectionApproval->employee_id = $approvalEmployeeID;
                $attendanceCorrectionApproval->status = AttendanceCorrectionApproval::StatusPending;
                $attendanceCorrectionApproval->save();
            }

            if ($attendanceCorrectionRequest->status == AttendanceCorrectionApproval::StatusApproved) {
                $this->changeCheckTime($attendanceCorrectionRequest);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approval(ApprovalCorrectionRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var AttendanceCorrectionRequest $approvalCorrectionRequest
             */
            $approvalCorrectionRequest = AttendanceCorrectionRequest::query()
                ->where('id', '=', $id)
                ->first();
            if (!$approvalCorrectionRequest) {
                return response()->json([
                    'status' => false,
                    'message' => "Attendance correction request not found !"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var AttendanceCorrectionApproval $userApproval
             */
            $userApproval = $approvalCorrectionRequest->attendanceCorrectionApprovals()
                ->where('employee_id', '=', $user->employee_id)
                ->where('status', '=', AttendanceCorrectionApproval::StatusPending)
                ->first();
            if (!$userApproval) {
                return response()->json([
                    'status' => false,
                    'message' => 'You don\'t have access to approve this request',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($userApproval->priority > 0) {
                $beforeApproval = $approvalCorrectionRequest->attendanceCorrectionApprovals()
                    ->where('priority', '<', $userApproval->priority)
                    ->where('status', '=', AttendanceCorrectionApproval::StatusPending)
                    ->exists();
                if ($beforeApproval) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Last approver not doing approval yet!',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            DB::beginTransaction();

            $userApproval->status = $request->input('status');
            $userApproval->notes = $request->input('notes');
            $userApproval->save();

            if ($userApproval->status == AttendanceCorrectionApproval::StatusApproved) {
                $lastApproval = $approvalCorrectionRequest->attendanceCorrectionApprovals()
                    ->where('priority', '>', $userApproval->priority)
                    ->where('status', '=', AttendanceCorrectionApproval::StatusPending)
                    ->exists();
                if (!$lastApproval) {
                    $approvalCorrectionRequest->status = AttendanceCorrectionApproval::StatusApproved;

                    $this->changeCheckTime($approvalCorrectionRequest);
                }
            } else if ($userApproval->status == AttendanceCorrectionApproval::StatusRejected) {
                $approvalCorrectionRequest->status = AttendanceCorrectionApproval::StatusRejected;

                /**
                 * @var AttendanceCorrectionApproval[] $attendanceCorrectionApprovals
                 */
                $attendanceCorrectionApprovals = $approvalCorrectionRequest->attendanceCorrectionApprovals()
                    ->where('priority', '>', $userApproval->priority)
                    ->get();
                foreach ($attendanceCorrectionApprovals as $attendanceCorrectionApproval) {
                    $attendanceCorrectionApproval->status = AttendanceCorrectionApproval::StatusRejected;
                    $attendanceCorrectionApproval->save();
                }
            }

            $approvalCorrectionRequest->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws Throwable
     */
    public function changeCheckTime(AttendanceCorrectionRequest $attendanceCorrectionRequest) {
        $expectedCheckIn = explode(":", $attendanceCorrectionRequest->check_in_time);
        $expectedCheckOut = explode(":", $attendanceCorrectionRequest->check_out_time);

        /**
         * @var EmployeeAttendance $employeeAttendance
         */
        $employeeAttendance = EmployeeAttendance::query()
            ->where('id', '=', $attendanceCorrectionRequest->employee_attendance_id)
            ->first();
        if (!$employeeAttendance) {
            throw new \Exception("employee attendance not found");
        }

        $checkInTime = Carbon::parse($employeeAttendance->real_check_in)
            ->setTimezone($employeeAttendance->check_in_tz)
            ->setHour($expectedCheckIn[0])
            ->setMinute($expectedCheckIn[1])
            ->setSecond($expectedCheckIn[2])
            ->setTimezone('UTC');

        $checkOutTime = Carbon::parse($employeeAttendance->real_check_out)
            ->setTimezone($employeeAttendance->check_out_tz)
            ->setHour($expectedCheckOut[0])
            ->setMinute($expectedCheckOut[1])
            ->setSecond($expectedCheckOut[2])
            ->setTimezone('UTC');

        $employeeAttendance->real_check_in = $checkInTime->format('Y-m-d H:i:s');
        $employeeAttendance->real_check_out = $checkOutTime->format('Y-m-d H:i:s');
        $employeeAttendance->notes = null;
        $employeeAttendance->is_need_approval = false;
        $employeeAttendance->approved = true;
        $employeeAttendance->save();

        switch ($attendanceCorrectionRequest->reference_type) {
            case EmployeeAttendance::AttendanceTypeNormal:

                /**
                 * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
                 */
                $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()
                    ->where('id', '=', $attendanceCorrectionRequest->reference_id)
                    ->first();
                if (!$employeeTimesheetSchedule) {
                    throw new \Exception("employee timesheet schedule not found");
                }

                $employeeTimesheetSchedule->check_in_time = $employeeAttendance->real_check_in;
                $employeeTimesheetSchedule->check_in_latitude = $employeeAttendance->checkin_lat;
                $employeeTimesheetSchedule->check_in_longitude = $employeeAttendance->checkin_long;
                $employeeTimesheetSchedule->check_in_timezone = $employeeAttendance->check_in_tz;
                $employeeTimesheetSchedule->check_out_time = $employeeAttendance->real_check_out;
                $employeeTimesheetSchedule->check_out_latitude = $employeeAttendance->checkout_lat;
                $employeeTimesheetSchedule->check_out_longitude = $employeeAttendance->checkout_long;
                $employeeTimesheetSchedule->check_out_timezone = $employeeAttendance->check_out_tz;
                $employeeTimesheetSchedule->save();
                break;
            case EmployeeAttendance::AttendanceTypeOvertime:
                /**
                 * @var OvertimeEmployee $overtimeEmployee
                 */
                $overtimeEmployee = OvertimeEmployee::query()
                    ->where('id', '=', $attendanceCorrectionRequest->reference_id)
                    ->first();
                if (!$overtimeEmployee) {
                    throw new \Exception("employee overtime not found");
                }

                $overtimeEmployee->check_in_time = $employeeAttendance->real_check_in;
                $overtimeEmployee->check_in_lat = $employeeAttendance->checkin_lat;
                $overtimeEmployee->check_in_long = $employeeAttendance->checkin_long;
                $overtimeEmployee->check_in_timezone = $employeeAttendance->check_in_tz;
                $overtimeEmployee->check_out_time = $employeeAttendance->real_check_out;
                $overtimeEmployee->check_out_lat = $employeeAttendance->checkout_lat;
                $overtimeEmployee->check_out_long = $employeeAttendance->checkout_long;
                $overtimeEmployee->check_out_timezone = $employeeAttendance->check_out_tz;
                $overtimeEmployee->save();
                break;
            case EmployeeAttendance::AttendanceTypeBackup:
                /**
                 * @var BackupEmployeeTime $backupEmployeeTime
                 */
                $backupEmployeeTime = BackupEmployeeTime::query()
                    ->where('id', '=', $attendanceCorrectionRequest->reference_id)
                    ->first();
                if (!$backupEmployeeTime) {
                    throw new \Exception("backup employee time not found");
                }

                $backupEmployeeTime->check_in_time = $employeeAttendance->real_check_in;
                $backupEmployeeTime->check_in_lat = $employeeAttendance->checkin_lat;
                $backupEmployeeTime->check_in_long = $employeeAttendance->checkin_long;
                $backupEmployeeTime->check_in_timezone = $employeeAttendance->check_in_tz;
                $backupEmployeeTime->check_out_time = $employeeAttendance->real_check_out;
                $backupEmployeeTime->check_out_lat = $employeeAttendance->checkout_lat;
                $backupEmployeeTime->check_out_long = $employeeAttendance->checkout_long;
                $backupEmployeeTime->check_out_timezone = $employeeAttendance->check_out_tz;
                $backupEmployeeTime->save();
                break;
        }
    }
}
