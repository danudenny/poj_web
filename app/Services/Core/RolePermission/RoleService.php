<?php

namespace App\Services\Core\RolePermission;

use App\Models\Role_Permission\Permission;
use App\Models\Role_Permission\Role;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RoleService extends BaseService {

    public function __construct (Role $role) {
        $this->role = $role;
    }

    public function index($request): JsonResponse
    {
        $roles = $this->role->with('permissions');

        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch data for roles',
            'data' => $roles->paginate($request->per_page ?? 10)
        ], 200);
    }

    public function save($request): JsonResponse
    {
        $roles = $this->role->whereRaw('LOWER(name) = ?', strtolower($request->name))->first();
        if ($roles) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role name already exists',
                'data' => null
            ], 400);
        }

        DB::beginTransaction();
        try {
            $role = $this->role->create([
                'name' => $request->name,
                'role_type' => $request->role_type,
                'role_level' => $request->role_level,
            ]);

            if ($request->has('permissions')) {
                $permissionsData = $request->input('permissions');

                $role->permissions()->detach();
                foreach ($permissionsData as $permissionData) {
                    $permissionId = $permissionData['permission_id'];
                    $actions = $permissionData['actions'];

                    $permission = Permission::find($permissionId);

                    $role->permissions()->attach($permission, [
                        'is_create' => $actions['create'] ?? false,
                        'is_read' => $actions['read'] ?? false,
                        'is_update' => $actions['update'] ?? false,
                        'is_delete' => $actions['delete'] ?? false,
                    ]);
                }
            }

            if (!$role->save()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save role',
                    'data' => null
                ], 400);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success save role',
                'data' => $role
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    public function update($id, $request): JsonResponse
    {
        $role = Role::findOrFail($id);

        DB::beginTransaction();
        try {
            $role->update([
                'name' => $request->name,
                'role_type' => $request->role_type,
                'role_level' => $request->role_level,
            ]);

            if ($request->has('permissions')) {
                $permissionsData = $request->input('permissions');

                $permissions = Permission::whereIn('id', array_column($permissionsData, 'permission_id'))->get();

                $role->permissions()->sync(
                    $permissions->mapWithKeys(function ($permission) use ($permissionsData) {
                        $permissionData = collect($permissionsData)->firstWhere('permission_id', $permission->id);
                        $actionIds = collect($permissionData['actions'])->filter(function ($value) {
                            return $value === true;
                        })->keys()->all();

                        return [$permission->id => ['action_id' => $actionIds]];
                    })
                );
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success update role',
                'data' => $role
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
}
