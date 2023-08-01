<?php

namespace App\Services\Core\RolePermission;

use App\Models\Role_Permission\Permission;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function __construct(Permission $permission)
    {
        $this->permission = $permission;
    }

    public function index($request): JsonResponse
    {
        $permissions = $this->permission->with('roles');
        $permissions->when($request->name, function ($query) use ($request) {
            return $query->whereRaw('LOWER(name) LIKE ?', '%' . strtolower($request->name) . '%');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch data for permissions',
            'data' => $permissions->paginate($request->per_page ?? 10)
        ], 200);
    }

    public function save($request): JsonResponse
    {
        $permissions = $this->permission->whereRaw('LOWER(permission_name) = ?', strtolower($request->permission_name))->first();
        if ($permissions) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission name already exists',
                'data' => null
            ], 400);
        }

        DB::beginTransaction();
        try {
            $permission = $this->permission->create([
                'permission_name' => $request->permission_name,
            ]);

            if (!$permission->save()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save permission',
                    'data' => null
                ], 400);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success save permission',
                'data' => $permission
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save permission',
                'data' => null
            ], 400);
        }
    }
}
