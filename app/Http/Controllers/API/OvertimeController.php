<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Overtime\CreateOvertimeRequest;
use App\Services\Core\OvertimeService;

class OvertimeController extends Controller
{
    /**
     * @param CreateOvertimeRequest $request
     * @param OvertimeService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateOvertimeRequest $request, OvertimeService $service) {
        return $service->create($request);
    }
}
