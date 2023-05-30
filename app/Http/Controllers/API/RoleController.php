<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Role\RoleSaveRequest;
use App\Http\Requests\Role\RoleUpdateRequest;
use App\Services\Core\RoleService;
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
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
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
     * @return \Illuminate\Http\Response
     */
    public function getPermissions()
    {
        try {
            $result = $this->roleService->getPermissions();
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {

            return $this->sendError($error->getMessage());
        }
    }

    /**
     * view spesific resource
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request)
    {
        try {
            $id = $request->only('id')['id'];
            $result = $this->roleService->view($id);

            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * store resource
     * @param RoleSaveRequest $request
     * @return \Illuminate\Http\Response
     */
    public function save(RoleSaveRequest $request)
    {
        try {
            $result = $this->roleService->save($request);
            return $this->sendSuccess($result, self::SUCCESS_CREATED, 201);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Update spesific resource
     * @param RoleUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(RoleUpdateRequest $request)
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
     * @return \Illuminate\Http\Response
     */
    public function toggleRoleStatus(Request $request) {
        try {
            $result = $this->roleService->toggleRoleStatus($request);
            return $this->sendSuccess($result, self::SUCCESS_UPDATED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Softdelete data role
     * @param Request $request
     * @return \Illuminate\Http\Response|void
     */
    public function delete(Request $request)
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
     * @return \Illuminate\Http\Response|void
     */
    public function restore(Request $request)
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
     * @return \Illuminate\Http\Response|void
     */
    public function destroy(Request $request)
    {
        try {
            $result = $this->roleService->destroy($request);
            return $this->sendSuccess($result, self::SUCCESS_DESTROYED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }


}
