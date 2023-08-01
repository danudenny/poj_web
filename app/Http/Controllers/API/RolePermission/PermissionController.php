<?php

namespace App\Http\Controllers\API\RolePermission;

use App\Http\Controllers\Controller;
use App\Services\Core\RolePermission\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService) {
        $this->permissionService = $permissionService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->permissionService->index($request);
    }

    public function save(Request $request): JsonResponse
    {
        return $this->permissionService->save($request);
    }
}
