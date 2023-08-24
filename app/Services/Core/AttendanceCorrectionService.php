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

        $query = AttendanceCorrectionRequest::query()->with(['employee']);

        $requestorEmployeeID = null;

        if ($this->isRequestedRoleLevel(Role::RoleStaff)) {
            $requestorEmployeeID = $user->employee_id;
        }

        if ($requestorEmployeeID) {
            $query->where('employee_id', '=', $requestorEmployeeID);
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

        $attendanceCorrectionRequest = AttendanceCorrectionRequest::query()
            ->with(['employee'])
            ->where('id', '=', $id)
            ->first();
        if (!$attendanceCorrectionRequest) {
            return response()->json([
                'status' => false,
                'message' => 'Attendance correction request not found!'
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

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

        $query = AttendanceCorrectionApproval::query()
            ->where('employee_id', '=', $user->employee_id)
            ->orderBy('id', 'DESC');

        if ($status = $request->query('status')) {
            $query->where('status', '=', $status);
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

            /**
             * @var EmployeeAttendance $employeeAttendance
             */
            $employeeAttendance = EmployeeAttendance::query()
                ->where('id', '=', $request->input('employee_attendance_id'))
                ->where('employee_id', '=', $user->employee_id)
                ->first();
            if (!$employeeAttendance) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee attendance not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($employeeAttendance->real_check_in == null || $employeeAttendance->real_check_out == null) {
                return response()->json([
                    'status' => false,
                    'message' => 'You need to check in / check out first before request correction!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $data = [
                'reference_type' => $employeeAttendance->attendance_types,
                'reference_id' => null,
            ];

            if ($data['reference_type'] == EmployeeAttendance::AttendanceTypeNormal) {
                /**
                 * @var EmployeeTimesheetSchedule $employeeTimesheetSchedule
                 */
                $employeeTimesheetSchedule = EmployeeTimesheetSchedule::query()
                    ->where('employee_attendance_id', '=', $employeeAttendance->id)
                    ->first();
                if (!$employeeTimesheetSchedule) {
                    return response()->json([
                        'status' => false,
                        'message' => 'There is no employee timesheet schedule!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                $data['reference_id'] = $employeeTimesheetSchedule->id;
            } else if ($data['reference_type'] == EmployeeAttendance::AttendanceTypeBackup) {
                /**
                 * @var BackupEmployeeTime $backupEmployeeTime
                 */
                $backupEmployeeTime = BackupEmployeeTime::query()
                    ->where('employee_attendance_id', '=', $employeeAttendance->id)
                    ->first();
                if (!$backupEmployeeTime) {
                    return response()->json([
                        'status' => false,
                        'message' => 'There is no backup employee time!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                $data['reference_id'] = $backupEmployeeTime->id;
            } else if ($data['reference_type'] == EmployeeAttendance::AttendanceTypeOvertime) {
                /**
                 * @var OvertimeEmployee $overtimeEmployee
                 */
                $overtimeEmployee = OvertimeEmployee::query()
                    ->where('employee_attendance_id', '=', $employeeAttendance->id)
                    ->first();
                if (!$overtimeEmployee) {
                    return response()->json([
                        'status' => false,
                        'message' => 'There is no employee overtime!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                $data['reference_id'] = $overtimeEmployee->id;
            }

            if ($data['reference_id'] == null) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no attendance reference!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $approvalEmployeeIDs = [];
            $approvalUsers = $this->approvalService->getApprovalUser($user->employee, ApprovalModule::ApprovalAttendanceCorrection);
            foreach ($approvalUsers as $approvalUser) {
                $approvalEmployeeIDs[] = $approvalUser->employee_id;
            }

            DB::beginTransaction();

            $attendanceCorrectionRequest = new AttendanceCorrectionRequest();
            $attendanceCorrectionRequest->employee_id = $user->employee_id;
            $attendanceCorrectionRequest->employee_attendance_id = $employeeAttendance->id;
            $attendanceCorrectionRequest->reference_type = $data['reference_type'];
            $attendanceCorrectionRequest->reference_id = $data['reference_id'];
            $attendanceCorrectionRequest->status = AttendanceCorrectionApproval::StatusPending;
            $attendanceCorrectionRequest->check_in_time = $request->input('check_in_time');
            $attendanceCorrectionRequest->check_out_time = $request->input('check_out_time');
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
                $employeeTimesheetSchedule->check_out_time = $employeeAttendance->real_check_out;
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
                $overtimeEmployee->check_out_time = $employeeAttendance->real_check_out;
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
                $backupEmployeeTime->check_out_time = $employeeAttendance->real_check_out;
                $backupEmployeeTime->save();
                break;
        }
    }
}
