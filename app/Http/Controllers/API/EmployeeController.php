<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Services\Core\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends BaseController
{
    private EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService) {
        $this->employeeService = $employeeService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            return $this->employeeService->index($request);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function view($id): JsonResponse
    {
        try {
            $result = $this->employeeService->view($id);
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function syncToUser(): JsonResponse
    {
        try {
            return $this->employeeService->syncToUser();

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function listPaginatedEmployee(Request $request): JsonResponse {
        return $this->employeeService->listPaginatedEmployees($request);
    }

}
