<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Role\RoleSaveRequest;
use App\Http\Requests\Role\RoleUpdateRequest;
use App\Services\Core\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseController
{
    private RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->roleService->index($request);
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Get all data permissions
     * @return JsonResponse
     */
    public function getPermissions(): JsonResponse
    {
        try {
            $result = $this->roleService->getPermissions();
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {

            return $this->sendError($error->getMessage());
        }
    }

    /**
     * view specific resource
     * @param Request $request
     * @return JsonResponse
     */
    public function view(Request $request): JsonResponse
    {
        try {
            $id = $request->only('id')['id'];
            return $this->roleService->view($id);
        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * store resource
     * @param RoleSaveRequest $request
     * @return JsonResponse
     */
    public function save(RoleSaveRequest $request): JsonResponse
    {
        try {
            $result = $this->roleService->save($request);
            return $this->sendSuccess($result, self::SUCCESS_CREATED, 201);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Update specific resource
     * @param RoleUpdateRequest $request
     * @return JsonResponse
     */
    public function update(RoleUpdateRequest $request): JsonResponse
    {
        try {
            $result = $this->roleService->update($request);
            return $this->sendSuccess($result, self::SUCCESS_UPDATED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleRoleStatus(Request $request): JsonResponse
    {
        try {
            $result = $this->roleService->toggleRoleStatus($request);
            return $this->sendSuccess($result, self::SUCCESS_UPDATED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Soft-delete data role
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $result = $this->roleService->delete($request);
            return $this->sendSuccess($result, self::SUCCESS_DELETED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Restore data role
     * @param Request $request
     * @return JsonResponse
     */
    public function restore(Request $request): JsonResponse
    {
        try {
            $result = $this->roleService->restore($request);
            return $this->sendSuccess($result, self::SUCCESS_RESTORE);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Destroy data role
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $result = $this->roleService->destroy($request);
            return $this->sendSuccess($result, self::SUCCESS_DESTROYED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }


}
