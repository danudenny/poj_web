<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\Core\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{

    private DepartmentService $departmentService;

    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    public function all(Request $request): JsonResponse
    {
        return $this->departmentService->getAll($request);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->departmentService->index($request);
    }

    public function show($id, $unit_id): JsonResponse
    {
        return $this->departmentService->show($id, $unit_id);
    }

    public function assign($id): JsonResponse
    {
        return $this->departmentService->assignCompany($id);
    }

    public function assignTeam(Request $request, $id, $unit_id): JsonResponse
    {
        return $this->departmentService->assignTeam($request, $id, $unit_id);
    }
}
