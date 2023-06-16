<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Permission\PermissionSaveRequest;
use App\Http\Requests\Permission\PermissionUpdateRequest;
use App\Services\Core\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends BaseController
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService) {
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        try {
            $result = $this->permissionService->index($request);
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function view(Request $request)
    {
        try {
            $id = $request->only('id')['id'];
            $result = $this->permissionService->view($id);
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function save(PermissionSaveRequest $request)
    {
        try {
            $result = $this->permissionService->save($request);
            return $this->sendSuccess($result, self::SUCCESS_CREATED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function update(PermissionUpdateRequest $request)
    {
        try {
            $result = $this->permissionService->update($request);
            return $this->sendSuccess($result, self::SUCCESS_UPDATED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $result = $this->permissionService->destroy($request);
            return $this->sendSuccess($result, self::SUCCESS_DESTROYED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }


}
