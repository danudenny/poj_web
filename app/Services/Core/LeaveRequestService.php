<?php

namespace App\Services\Core;

use App\Helpers\Notification\NotificationScreen;
use App\Http\Requests\LeaveRequest\ApprovalRequest;
use App\Models\ApprovalModule;
use App\Models\BackupEmployeeTime;
use App\Models\Employee;
use App\Models\EmployeeNotification;
use App\Models\EmployeeTimesheetSchedule;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestApproval;
use App\Models\LeaveRequestHistory;
use App\Models\MasterLeave;
use App\Models\OvertimeEmployee;
use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use App\Services\MinioService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class LeaveRequestService extends BaseService {

    private ApprovalService $approvalService;

    private MinioService $minioService;

    public function __construct(MinioService $minioService) {
        $this->minioService = $minioService;
        $this->approvalService = new ApprovalService();
    }

    public function index(Request $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = auth()->user();

        $unitRelationID = $request->get('unit_relation_id');
        $leaveRequest = LeaveRequest::query()->with(['employee', 'leaveType', 'leaveHistory']);
        $leaveRequest->join('employees', 'employees.id', '=', 'leave_requests.employee_id');

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
            $leaveRequest->leftJoin('user_operating_units', 'user_operating_units.unit_relation_id', '=', 'employees.default_operating_unit_id');
            $leaveRequest->where(function (Builder $builder) use ($user) {
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
            $leaveRequest->join(DB::raw($subQuery), function (JoinClause $joinClause) {
                $joinClause->on(DB::raw("relatedJob.odoo_job_id"), '=', DB::raw('employees.job_id'))
                    ->where(DB::raw("relatedJob.unit_relation_id"), '=', DB::raw('employees.unit_id'));
            });

            $leaveRequest->where(function (Builder $builder) use ($user) {
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
            $leaveRequest->where(function(Builder $builder) use ($unitRelationID) {
                $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                        ->orWhere('employees.cabang_id', '=', $unitRelationID)
                        ->orWhere('employees.area_id', '=', $unitRelationID)
                        ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                        ->orWhere('employees.corporate_id', '=', $unitRelationID);
                });
            });
        }

        $leaveRequest->when($request->employee_id, function ($query) use ($request) {
            $query->where('leave_requests.employee_id', $request->employee_id);
        });
        $leaveRequest->when($request->leave_type_id, function ($query) use ($request) {
            $query->where('leave_requests.leave_type_id', $request->leave_type_id);
        });
        $leaveRequest->when($request->start_date, function ($query) use ($request) {
            $query->where('leave_requests.start_date', $request->start_date);
        });
        $leaveRequest->when($request->end_date, function ($query) use ($request) {
            $query->where('leave_requests.end_date', $request->end_date);
        });
        $leaveRequest->when($request->last_status, function ($query) use ($request) {
            $query->where('leave_requests.last_status', $request->last_status);
        });

        $leaveRequest->select(['leave_requests.*'])
            ->groupBy('leave_requests.id')
            ->orderBy('leave_requests.id', 'DESC');

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully get leave request data',
            'data' => $this->list($leaveRequest, $request)
        ]);
    }

    public function listApprovals(Request $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $unitRelationID = $request->get('unit_relation_id');
            $query = LeaveRequestApproval::query()->with(['leaveRequest', 'employee', 'leaveRequest.employee', 'leaveRequest.leaveType']);
            $query->join('leave_requests', 'leave_requests.id', '=', 'leave_request_approvals.leave_request_id');
            $query->join('employees AS reqEmployee', 'reqEmployee.id', '=', 'leave_requests.employee_id');
            $query->join('employees AS approverEmployee', 'approverEmployee.id', '=', 'leave_request_approvals.employee_id');

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
                $query->where('leave_request_approvals.employee_id', '=', $user->employee_id);
            }

            if ($unitRelationID) {
                $query->where(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                        $builder->orWhere('reqEmployee.outlet_id', '=', $unitRelationID)
                            ->orWhere('reqEmployee.cabang_id', '=', $unitRelationID)
                            ->orWhere('reqEmployee.area_id', '=', $unitRelationID)
                            ->orWhere('reqEmployee.kanwil_id', '=', $unitRelationID)
                            ->orWhere('reqEmployee.corporate_id', '=', $unitRelationID);
                    })->orWhere(function(Builder $builder) use ($unitRelationID) {
                        $builder->orWhere('approverEmployee.outlet_id', '=', $unitRelationID)
                            ->orWhere('approverEmployee.cabang_id', '=', $unitRelationID)
                            ->orWhere('approverEmployee.area_id', '=', $unitRelationID)
                            ->orWhere('approverEmployee.kanwil_id', '=', $unitRelationID)
                            ->orWhere('approverEmployee.corporate_id', '=', $unitRelationID);
                    });
                });
            }

            if ($status = $request->query('status')) {
                $query->where('leave_request_approvals.status', '=', $status);
            }

            $query->select(['leave_request_approvals.*']);
            $query->groupBy('leave_request_approvals.id');
            $query->orderBy('leave_request_approvals.id', 'DESC');

            return response()->json([
                'status' => true,
                'message' => 'Success!',
                'data' => $this->list($query, $request)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        $leaveRequest = LeaveRequest::with(['employee', 'leaveType', 'leaveHistory'])->find($id);
        if (!$leaveRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave request data not found'
            ], 404);
        }

        $leaveRequest->append(['is_can_approve']);
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully get leave request data',
            'data' => $leaveRequest
        ]);
    }

    public function save($request): JsonResponse
    {
        /**
         * @var Employee $employee
         */
        $employee = auth()->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee data not found'
            ], 404);
        }

        $checkLeaveRequest = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where(function(Builder $builder) use ($request) {
                $builder->orWhere(function(Builder $builder) use ($request) {
                    $builder->where( 'start_date', '<=', $request->start_date)
                        ->where('end_date', '>=', $request->start_date);
                })->orWhere(function(Builder $builder) use ($request) {
                    $builder->where('start_date', '<=', $request->end_date)
                        ->where('end_date', '>=', $request->end_date);
                });
            })
            ->whereNot('last_status', LeaveRequest::StatusRejected)
            ->first();

        if ($checkLeaveRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda memiliki Request Izin/Cuti Yang Active Pada Tanggal Tersebut'
            ], 400);
        }

        $leaveType = MasterLeave::find($request->leave_type_id);
        if (!$leaveType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave type data not found'
            ], 404);
        }

        $approverEmployeeIDs = [];
        $approverEmployees = $this->approvalService->getApprovalUser($employee, ApprovalModule::ApprovalLeave);
        foreach ($approverEmployees as $approverEmployee) {
            $approverEmployeeIDs[] = $approverEmployee->employee_id;
        }

        DB::beginTransaction();
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $diffInDays = $startDate->diffInDays($endDate);

            if ($endDate < $startDate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tanggal Selesai Harus Lebih Besar Dari Tanggal Mulai'
                ], 400);
            }

            $status = LeaveRequest::StatusOnProcess;
            if (count($approverEmployeeIDs) == 0) {
                $status = LeaveRequest::StatusApproved;
            }

            $leaveRequest = LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $request->leave_type_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'days' => $diffInDays + 1,
                'reason' => $request->reason,
                'last_status' => $status,
                'file_url' => $request->file_url
            ]);

            foreach ($approverEmployeeIDs as $index => $approverEmployeeID) {
                $leaveRequestApproval = new LeaveRequestApproval();
                $leaveRequestApproval->priority = $index;
                $leaveRequestApproval->leave_request_id = $leaveRequest->id;
                $leaveRequestApproval->employee_id = $approverEmployeeID;
                $leaveRequestApproval->status = LeaveRequestApproval::StatusPending;
                $leaveRequestApproval->save();

                $this->getNotificationService()->createNotification(
                    $approverEmployeeID,
                    'Approval Izin/Cuti',
                    "Halo, anda memiliki daftar permintaan persetujuan izin/ cuti. Klik di sini untuk membuka halaman persetujuan izin/ cuti.",
                    "Halo, anda memiliki daftar permintaan persetujuan izin/ cuti. Klik di sini untuk membuka halaman persetujuan izin/ cuti.",
                    EmployeeNotification::ReferenceLeave,
                    $leaveRequest->id
                )->withMobileScreen(NotificationScreen::MobileLeavePermissionLeave, [
                    'active_tab' => 2
                ])->withSendPushNotification()->send();
            }

            if($status === LeaveRequest::StatusApproved) {
                $this->removeSchedule($leaveRequest);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully create leave request data'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function approval(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            /**
             * @var Employee $employee
             */
            $employee = $request->user()->employee;

            /**
             * @var LeaveRequest $leaveRequest
             */
            $leaveRequest = LeaveRequest::find($id);
            if (!$leaveRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request Izin/Cuti Tidak Ditemukan'
                ], 404);
            }

            /**
             * @var LeaveRequestApproval $leaveRequestApproval
             */
            $leaveRequestApproval = LeaveRequestApproval::query()
                ->where('leave_request_id', '=', $leaveRequest->id)
                ->where('employee_id', '=', $employee->id)
                ->where('status', '=', LeaveRequestApproval::StatusPending)
                ->first();
            if (!$leaveRequestApproval) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kamu tidak memiliki akses!'
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            if ($leaveRequestApproval->priority > 0) {
                $beforeApprovalExist = $leaveRequest->leaveRequestApprovals()
                    ->where('priority', '<', $leaveRequestApproval->priority)
                    ->where('status', '=', LeaveRequestApproval::StatusPending)
                    ->exists();
                if ($beforeApprovalExist) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Menunggu persetujuan sebelumya!',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            DB::beginTransaction();

            $leaveRequestApproval->status = $request->input('status');
            $leaveRequestApproval->notes = $request->input('notes');
            $leaveRequestApproval->save();

            if ($leaveRequestApproval->status == LeaveRequestApproval::StatusRejected) {
                $leaveRequest->last_status = LeaveRequestApproval::StatusRejected;
                $leaveRequest->save();

                /**
                 * @var LeaveRequestApproval[] $nextApprovals
                 */
                $nextApprovals = $leaveRequest->leaveRequestApprovals()
                    ->where('priority', '>', $leaveRequestApproval->priority)
                    ->get();
                foreach ($nextApprovals as $nextApproval) {
                    $nextApproval->status = LeaveRequestApproval::StatusRejected;
                    $nextApproval->save();
                }
            } else if ($leaveRequestApproval->status == LeaveRequestApproval::StatusApproved) {
                $isNextApprovalExist = $leaveRequest->leaveRequestApprovals()
                    ->where('priority', '>', $leaveRequestApproval->priority)
                    ->exists();
                if (!$isNextApprovalExist) {
                    $leaveRequest->last_status = LeaveRequestApproval::StatusApproved;
                    $leaveRequest->save();

                    $this->removeSchedule($leaveRequest);
                }
            }

            if ($leaveRequest->last_status == LeaveRequestApproval::StatusApproved) {
                $this->getNotificationService()->createNotification(
                    $leaveRequest->employee_id,
                    'Izin/Cuti Disetujui',
                    "Halo, pengajuan izin/ cuti anda {$leaveRequest->start_date} - {$leaveRequest->end_date} telah disetujui. Klik di sini untuk melihat status persetujuan atas pengajuan izin/ cuti anda.",
                    "Halo, pengajuan izin/ cuti anda {$leaveRequest->start_date} - {$leaveRequest->end_date} telah disetujui. Klik di sini untuk melihat status persetujuan atas pengajuan izin/ cuti anda.",
                    EmployeeNotification::ReferenceLeave,
                    $leaveRequest->id
                )->withMobileScreen(NotificationScreen::MobileLeavePermissionLeave, [
                    'active_tab' => 1,
                    'active_sub_tab' => 2
                ])->withSendPushNotification()->send();
            } else if ($leaveRequest->last_status == LeaveRequestApproval::StatusRejected) {
                $this->getNotificationService()->createNotification(
                    $leaveRequest->employee_id,
                    'Izin/Cuti Ditolak',
                    "Halo, pengajuan izin/ cuti anda {$leaveRequest->start_date} - {$leaveRequest->end_date} ditolak. Klik di sini untuk melihat status persetujuan atas pengajuan izin/ cuti anda.",
                    "Halo, pengajuan izin/ cuti anda {$leaveRequest->start_date} - {$leaveRequest->end_date} ditolak. Klik di sini untuk melihat status persetujuan atas pengajuan izin/ cuti anda.",
                    EmployeeNotification::ReferenceLeave,
                    $leaveRequest->id
                )->withMobileScreen(NotificationScreen::MobileLeavePermissionLeave, [
                    'active_tab' => 1,
                    'active_sub_tab' => 1
                ])->withSendPushNotification()->send();
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Sukses'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reject($id): JsonResponse
    {
        $leaveRequest = LeaveRequest::find($id);
        if (!$leaveRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave request data not found'
            ], 404);
        }

        // find if leave request already approved
        $leaveRequestHistory = LeaveRequestHistory::where('leave_request_id', $leaveRequest->id)
            ->where('status', 'approved')
            ->first();
        if ($leaveRequestHistory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave request already approved'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $leaveRequest->last_status = 'rejected';
            if (!$leaveRequest->save()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update leave request data'
                ], 500);
            }

            $leaveRequestHistory = LeaveRequestHistory::create([
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id,
                'created_by' => $leaveRequest->employee_id,
                'rejected_by' => auth()->user()->employee->id,
                'status' => 'rejected',
            ]);

            if (!$leaveRequestHistory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create leave request history data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully approve leave request data',
                'data' => $leaveRequest
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function upload($request): JsonResponse
    {

        $auth = auth()->user();
        $employee = $auth->employee;

        try {
            $path = 'uploads/leave-request/'.$employee->id;
            $uploadedPath = $this->minioService->uploadFile($this->fromBase64($request->file), $path);

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully upload file leave request data',
                'data' => $uploadedPath
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function removeSchedule(LeaveRequest $leaveRequest) {
        /**
         * @var EmployeeTimesheetSchedule[] $normal
         */
        $normal = EmployeeTimesheetSchedule::query()
            ->join('periods', 'periods.id', '=', 'employee_timesheet_schedules.period_id')
            ->where('employee_timesheet_schedules.employee_id', '=', $leaveRequest->employee_id)
            ->whereRaw("(periods.year || '-' || periods.month || '-' || employee_timesheet_schedules.date)::DATE >= '$leaveRequest->start_date'::DATE")
            ->whereRaw("(periods.year || '-' || periods.month || '-' || employee_timesheet_schedules.date)::DATE <= '$leaveRequest->end_date'::DATE")
            ->select(['employee_timesheet_schedules.*'])
            ->get();

        foreach ($normal as $item) {
            $item->employeeAttendance?->delete();
            $item->delete();
        }

        /**
         * @var BackupEmployeeTime[] $backup
         */
        $backup = BackupEmployeeTime::query()
            ->join('backup_times', 'backup_times.id', '=', 'backup_employee_times.backup_time_id')
            ->where('backup_employee_times.employee_id', '=', $leaveRequest->employee_id)
            ->whereRaw("backup_times.backup_date >= '$leaveRequest->start_date'::DATE")
            ->whereRaw("backup_times.backup_date <= '$leaveRequest->end_date'::DATE")
            ->select(['backup_employee_times.*'])
            ->get();
        foreach ($backup as $item) {
            $item->employeeAttendance?->delete();
            $item->delete();
        }

        /**
         * @var OvertimeEmployee[] $overtime
         */
        $overtime = OvertimeEmployee::query()
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->where('overtime_employees.employee_id', '=', $leaveRequest->employee_id)
            ->whereRaw("overtime_dates.date >= '$leaveRequest->start_date'::DATE")
            ->whereRaw("overtime_dates.date <= '$leaveRequest->end_date'::DATE")
            ->select(['overtime_employees.*'])
            ->get();
        foreach ($overtime as $item) {
            $item->employeeAttendance?->delete();
            $item->delete();
        }
    }
}
