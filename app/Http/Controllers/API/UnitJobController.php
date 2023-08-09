<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUnitJobRequest;
use App\Http\Requests\Job\AssignParentJobRequest;
use App\Services\Core\UnitHasJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitJobController extends Controller
{
    public function index(Request $request, UnitHasJobService $service) {
        return $service->index($request);
    }

    public function assign(AssignParentJobRequest $request, UnitHasJobService $service) {
        return $service->assignParent($request);
    }

    public function create(CreateUnitJobRequest $request, UnitHasJobService $service) {
        return $service->createUnitJob($request);
    }

    public function chartView(UnitHasJobService $service): JsonResponse
    {
        return $service->chartView();
    }
}
