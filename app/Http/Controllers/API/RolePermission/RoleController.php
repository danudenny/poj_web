<?php

namespace App\Http\Controllers\API\RolePermission;

use App\Http\Controllers\Controller;
use App\Services\Core\RolePermission\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    private RoleService $roleService;

    public function __construct(RoleService $roleService) {
        $this->roleService = $roleService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->roleService->index($request);
    }

    public function save(Request $request): JsonResponse
    {
        return $this->roleService->save($request);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->roleService->update($request, $id);
    }
}
