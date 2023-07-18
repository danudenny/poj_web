<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Core\WorkReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkReportingController extends Controller
{
    private WorkReportingService $workReportingService;

    public function __construct(WorkReportingService $workReportingService)
    {
        $this->workReportingService = $workReportingService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->workReportingService->index($request);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->workReportingService->save($request);
    }

    public function show($id): JsonResponse
    {
        return $this->workReportingService->view($id);
    }

    public function edit(Request $request, $id): JsonResponse
    {
        return $this->workReportingService->update($request, $id);
    }

    public function delete($id): JsonResponse
    {
        return $this->workReportingService->delete($id);
    }

    public function createMandatoryWorkReporting(Request $request): JsonResponse
    {
        return $this->workReportingService->createMandatoryWorkReporting($request);
    }
}
