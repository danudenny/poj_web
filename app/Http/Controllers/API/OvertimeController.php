<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Overtime\CreateOvertimeRequest;
use App\Http\Requests\Overtime\OvertimeApprovalRequest;
use App\Services\Core\OvertimeService;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    /**
     * @param Request $request
     * @param OvertimeService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, OvertimeService $service) {
        return $service->index($request);
    }

    /**
     * @param Request $request
     * @param OvertimeService $service
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request, OvertimeService $service, int $id) {
        return $service->view($request, $id);
    }

    /**
     * @param CreateOvertimeRequest $request
     * @param OvertimeService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateOvertimeRequest $request, OvertimeService $service) {
        return $service->create($request);
    }

    /**
     * @param OvertimeApprovalRequest $request
     * @param OvertimeService $service
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approval(OvertimeApprovalRequest $request, OvertimeService $service, int $id) {
        return $service->approval($request, $id);
    }
}
