<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\Core\JobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{

    private JobService $jobService;

    public function __construct(JobService $jobService)
    {
        $this->jobService = $jobService;
    }

    public function index($id): JsonResponse
    {
        return $this->jobService->index($id);
    }

    public function allJobs(): JsonResponse
    {
        return $this->jobService->allJobs();
    }

    public function show(Request $request, $id): JsonResponse
    {
        return $this->jobService->getById($request, $id);
    }

    public function store(Request $request, $id): JsonResponse
    {
        return $this->jobService->store($request, $id);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->jobService->update($request, $id);
    }

    public function updateMandatoryReporting(Request $request, $id): JsonResponse
    {
        return $this->jobService->updateMandatoryReporting($request, $id);
    }

    public function delete($unitId, $jobId): JsonResponse
    {
        return $this->jobService->destroy($unitId, $jobId);
    }

}
