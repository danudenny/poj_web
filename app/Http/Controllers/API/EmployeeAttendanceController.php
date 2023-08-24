<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeAttendance\ApprovalEmployeeAttendance;
use App\Http\Requests\EmployeeAttendance\CheckInAttendanceRequest;
use App\Http\Requests\EmployeeAttendance\CheckOutAttendanceRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\Core\EmployeeAttendanceService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EmployeeAttendanceController extends BaseController
{
    private EmployeeAttendanceService $employeeAttendanceService;

    public function __construct(EmployeeAttendanceService $employeeAttendanceService) {
        $this->employeeAttendanceService = $employeeAttendanceService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->employeeAttendanceService->index($request);
    }

    public function view(Request $request, int $id) {
        return $this->employeeAttendanceService->view($request, $id);
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function checkIn(Request $request): JsonResponse
    {
        return $this->employeeAttendanceService->checkIn($request);
    }

    public function checkInV2(CheckInAttendanceRequest $request, int $id): JsonResponse
    {
        return $this->employeeAttendanceService->checkInV2($request, $id);
    }

    public function checkOutV2(CheckOutAttendanceRequest $request, int $id): JsonResponse
    {
        return $this->employeeAttendanceService->checkOutV2($request, $id);
    }

    /**
     * @throws GuzzleException
     */
    public function checkOut(Request $request): JsonResponse
    {
        return $this->employeeAttendanceService->checkOut($request);
    }

    public function approve(ApprovalEmployeeAttendance $request, int $id): JsonResponse
    {
        return $this->employeeAttendanceService->approve($request, $id);
    }

    public function getActiveeSchedule(Request $request) {
        return $this->employeeAttendanceService->getActiveAttendance($request);
    }

    public function getMonthlyEvaluate(Request $request) {
        return $this->employeeAttendanceService->monthEvaluate($request);
    }

    public function listApproval(Request $request) {
        return $this->employeeAttendanceService->getListApproval($request);
    }
}
