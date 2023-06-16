<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Services\Core\EmployeeDetailService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeDetailController extends BaseController
{
    private EmployeeDetailService $employeeDetailService;

    public function __construct(EmployeeDetailService $employeeDetailService)
    {
        $this->employeeDetailService = $employeeDetailService;
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            return $this->employeeDetailService->create($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
