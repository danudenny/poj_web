<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequest\ApprovalRequest;
use App\Services\Core\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class LeaveRequestController extends Controller
{
    private LeaveRequestService $leaveRequestService;

    public function __construct(LeaveRequestService $leaveRequestService) {
        $this->leaveRequestService = $leaveRequestService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->leaveRequestService->index($request);
    }

    public function show($id): JsonResponse
    {
        return $this->leaveRequestService->show($id);
    }

    public function save(Request $request): JsonResponse
    {
        return $this->leaveRequestService->save($request);
    }

    public function approve(ApprovalRequest $request, int $id): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Deprecated'
        ], ResponseAlias::HTTP_SERVICE_UNAVAILABLE);
    }

    public function reject($id): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Deprecated'
        ], ResponseAlias::HTTP_SERVICE_UNAVAILABLE);
    }

    public function approval(ApprovalRequest $request, int $id): JsonResponse
    {

        return $this->leaveRequestService->approval($request, $id);
    }

    public function upload(Request $request): JsonResponse
    {
        return $this->leaveRequestService->upload($request);
    }

    public function listApproval(Request $request): JsonResponse
    {
        return $this->leaveRequestService->listApprovals($request);
    }

    public function evaluate(Request $request) {
        return $this->leaveRequestService->evaluate($request);
    }
}
