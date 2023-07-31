<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Overtime\OvertimeCheckInRequest;
use App\Http\Requests\Overtime\CreateOvertimeRequest;
use App\Http\Requests\Overtime\OvertimeApprovalRequest;
use App\Http\Requests\Overtime\OvertimeCheckOutRequest;
use App\Services\Core\OvertimeService;
use Illuminate\Http\JsonResponse;
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

    public function employee_overtimes(Request $request, OvertimeService  $service) {
        return $service->listEmployeeOvertime($request);
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

    /**
     * @param OvertimeCheckInRequest $request
     * @param OvertimeService $service
     * @param int $id
     * @return JsonResponse|null
     */
    public function checkIn(OvertimeCheckInRequest $request, OvertimeService $service, int $id) {
        return  $service->checkIn($request, $id);
    }

    /**
     * @param OvertimeCheckOutRequest $request
     * @param OvertimeService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOut(OvertimeCheckOutRequest $request, OvertimeService $service, int $id) {
        return $service->checkOut($request, $id);
    }

    public function getActiveOvertime(Request $request, OvertimeService $service, int $id) {
        return $service->getActiveOvertime($request, $id);
    }

    public function getDetailEmployeeOvertime(Request $request, OvertimeService $service, int $id) {
        return $service->detailEmployeeOvertime($request, $id);
    }

    public function getListApproval(Request $request, OvertimeService $service) {
        return $service->listApprovalOvertime($request);
    }
}
