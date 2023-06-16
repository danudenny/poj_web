<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Services\Core\EmployeeAttendanceService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeAttendanceController extends BaseController
{
    private EmployeeAttendanceService $employeeAttendanceService;

    public function __construct(EmployeeAttendanceService $employeeAttendanceService) {
        $this->employeeAttendanceService = $employeeAttendanceService;
    }

    /**
     * @throws Exception
     */
    public function checkIn(Request $request, $id): JsonResponse
    {
        return $this->employeeAttendanceService->checkIn($request, $id);
    }

    public function checkOut(Request $request, $id): JsonResponse
    {
        return $this->employeeAttendanceService->checkOut($request, $id);
    }
}
