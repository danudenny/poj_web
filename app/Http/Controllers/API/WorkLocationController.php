<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Core\WorkLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkLocationController extends Controller
{
    private WorkLocationService $workLocationService;

    public function __construct(WorkLocationService $workLocationService) {
        $this->workLocationService = $workLocationService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->workLocationService->index($request);
    }

    public function show(Request $request): JsonResponse
    {
        return $this->workLocationService->show($request);
    }

    public function save(Request $request): JsonResponse
    {
        return $this->workLocationService->save($request);
    }

    public function update(Request $request): JsonResponse
    {
        return $this->workLocationService->edit($request);
    }

    public function delete($id): JsonResponse
    {
        return $this->workLocationService->delete($id);
    }
}
