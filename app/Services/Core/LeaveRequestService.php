<?php

namespace App\Services\Core;

use App\Http\Requests\LeaveRequest\ApprovalRequest;
use App\Models\ApprovalModule;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestApproval;
use App\Models\LeaveRequestHistory;
use App\Models\MasterLeave;
use App\Models\Role;
use App\Services\BaseService;
use App\Services\MinioService;
use Carbon\Carbon;
use Exception;
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

    public function index($request): JsonResponse
    {
        $auth = auth()->user();
        $employee = $auth->employee;
        $leaveRequest = LeaveRequest::with(['employee', 'leaveType', 'leaveHistory']);
        $leaveRequest->when($request->employee_id, function ($query) use ($request) {
            $query->where('employee_id', $request->employee_id);
        });
        $leaveRequest->when($request->leave_type_id, function ($query) use ($request) {
            $query->where('leave_type_id', $request->leave_type_id);
        });
        $leaveRequest->when($request->start_date, function ($query) use ($request) {
            $query->where('start_date', $request->start_date);
        });
        $leaveRequest->when($request->end_date, function ($query) use ($request) {
            $query->where('end_date', $request->end_date);
        });
        $leaveRequest->when($request->last_status, function ($query) use ($request) {
            $query->where('last_status', $request->last_status);
        });

        if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {
            $leaveRequest = $leaveRequest->paginate($request->per_page ?? 10);
        } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
            $leaveRequest = $leaveRequest->whereHas('employee', function ($query) use ($employee) {
                $query->where('corporate_id', $employee->last_unit->id);
                $query->orWhere('kanwil_id', $employee->last_unit->id);
                $query->orWhere('area_id', $employee->last_unit->id);
                $query->orWhere('cabang_id', $employee->last_unit->id);
                $query->orWhere('outlet_id', $employee->last_unit->id);
            })->paginate($request->per_page ?? 10);
        } else if ($this->isRequestedRoleLevel(Role::RoleStaff)) {
            $leaveRequest = $leaveRequest->where('employee_id', $auth->employee->id)->paginate($request->per_page ?? 10);;
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully get leave request data',
            'data' => $leaveRequest
        ]);
    }

    public function listApprovals(Request $request) {
        try {
            /**
             * @var Employee $employee
             */
            $employee = $request->user()->employee;

            $query = LeaveRequestApproval::query()->with(['leaveRequest', 'leaveRequest.employee'])
                ->where('employee_id', '=', $employee->id);

            if ($status = $request->query('status')) {
                $query->where('status', '=', $status);
            }

            $query->orderBy('id', 'DESC');

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

        $checkLeaveRequest = LeaveRequest::where('employee_id', $employee->id)
            ->where('start_date', $request->start_date)
            ->first();

        if ($checkLeaveRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have active leave request in the same start date'
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
                    'message' => 'End date must be greater than start date'
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
                    'message' => 'Leave request data not found'
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
                    'message' => 'You don\'t have access to do approval!'
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
                        'message' => 'Last approver not doing approval yet!',
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
                }
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Successfully approve leave request data'
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

}
