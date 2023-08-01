<?php

namespace App\Services\Core;

use App\Models\LeaveRequest;
use App\Models\LeaveRequestHistory;
use App\Models\MasterLeave;
use App\Services\BaseService;
use App\Services\MinioService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LeaveRequestService extends BaseService {

    private MinioService $minioService;
    public function __construct(MinioService $minioService) {
        $this->minioService = $minioService;
    }

    public function index($request): JsonResponse
    {
        $auth = auth()->user();
        $roles = $auth->roles->sortBy('priority')->first();
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

        if ($roles->role_level === 'superadmin') {
            $leaveRequest = $leaveRequest->paginate($request->per_page ?? 10);
        } else if ($roles->role_level === 'admin') {
            $leaveRequest = $leaveRequest->whereHas('employee', function ($query) use ($employee) {
                $query->where('corporate_id', $employee->last_unit->id);
                $query->orWhere('kanwil_id', $employee->last_unit->id);
                $query->orWhere('area_id', $employee->last_unit->id);
                $query->orWhere('cabang_id', $employee->last_unit->id);
                $query->orWhere('outlet_id', $employee->last_unit->id);
            })->paginate($request->per_page ?? 10);
        } else if ($roles->role_level === 'staff') {
            $leaveRequest = $leaveRequest->where('employee_id', $auth->employee->id)->paginate($request->per_page ?? 10);;
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully get leave request data',
            'data' => $leaveRequest
        ]);
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
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully get leave request data',
            'data' => $leaveRequest
        ]);
    }

    public function save($request): JsonResponse
    {
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

            $leaveRequest = LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $request->leave_type_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'days' => $diffInDays + 1,
                'reason' => $request->reason,
                'last_status' => 'on process',
                'file_url' => $request->file_url
            ]);

            if (!$leaveRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create leave request data'
                ], 500);
            }

            $leaveRequestHistory = LeaveRequestHistory::create([
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $employee->id,
                'created_by' => $employee->id,
                'status' => 'on process',
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
                'message' => 'Successfully create leave request data',
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

    public function approve($id): JsonResponse
    {
        $leaveRequest = LeaveRequest::find($id);
        if (!$leaveRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave request data not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $leaveRequest->last_status = 'approved';
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
                'approved_by' => auth()->user()->employee->id,
                'status' => 'approved',
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
