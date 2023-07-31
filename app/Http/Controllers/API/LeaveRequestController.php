<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Core\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function approve($id): JsonResponse
    {
        return $this->leaveRequestService->approve($id);
    }

    public function reject($id): JsonResponse
    {
        return $this->leaveRequestService->reject($id);
    }

    public function upload(Request $request): JsonResponse
    {
        return $this->leaveRequestService->upload($request);
    }
}
