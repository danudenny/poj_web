<?php

namespace App\Services\Core;

use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\ExtendRole;
use App\Models\Permission;
use App\Models\Role;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RoleService extends BaseService
{
    const DATA_EXISTS = 'Role already exists.';

    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function index($data): mixed
    {
        try {
            $roles = Role::query();
            $roles->with('permissions');
            $roles->when(request()->filled('name'), function (Builder $query) {
                $query->whereRaw('LOWER(name) LIKE ?', [strtolower('%' . request()->query('name') . '%')]);
            });
            $roles->when(request()->filled('is_active'), function ($query) {
                $query->where('is_active', '=', request()->query('is_active'));
            });
            $roles->orderBy('id', 'asc');

            return response()->json([
                'status' => 'success',
                'data' => $roles->get()
            ], 200);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function view($id): JsonResponse
    {
        try {
            $role = ExtendRole::with('permissions')->firstWhere('id', $id);

            if (!$role) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return response()->json([
                'status' => 'success',
                'data' => $role
            ], 200);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @return AnonymousResourceCollection
     * @throws Exception
     */
    public function getPermissions(): AnonymousResourceCollection
    {
        try {
            $permissions = Permission::get();

            if (empty($permissions)) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return PermissionResource::collection($permissions);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @param $request
     * @return Role
     * @throws Exception
     */
    public function save($request): Role
    {
        $role = Role::where('name', strtolower(str_replace(' ', '_', $request->name)))->first();
        if ($role) {
            throw new InvalidArgumentException(self::DATA_EXISTS, 400);
        }

        DB::beginTransaction();
        try {

            $priority = 0;
            if ($request->role_level == 'superadmin') {
                $priority = 1;
            } elseif ($request->role_level == 'admin') {
                $priority = 2;
            } elseif ($request->role_level == 'staff') {
                $priority = 3;
            }

            $role = new Role();
            $role->name = strtolower(str_replace(' ', '_', $request->name));
            $role->role_level = $request->role_level;
            $role->guard_name = 'web';
            $role->priority =  $priority;

            if (!$role->save()) {
                throw new Exception(self::DB_FAILED, 500);
            }

            if (!empty($request->permission)) {
                $role->syncPermissions($request->permission);
            }

            DB::commit();
            return $role;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws Exception
     */
    public function update($request): mixed
    {
        $role = Role::where('name', strtolower(str_replace(' ', '_', $request->name)))
            ->where('id', '!=', $request->id)
            ->first();
        if ($role) {
            throw new InvalidArgumentException(self::DATA_EXISTS, 400);
        }

        DB::beginTransaction();
        try {

            $role = Role::find($request->id);
            if (!$role) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            $priority = 0;
            if ($request->role_level == 'superadmin') {
                $priority = 1;
            } elseif ($request->role_level == 'admin') {
                $priority = 2;
            } elseif ($request->role_level == 'staff') {
                $priority = 3;
            }

            $role->name = strtolower(str_replace(' ', '_', $request->name));
            $role->role_level = $request->role_level;
            $role->is_active = $request->is_active;
            $role->guard_name = 'web';
            $role->priority =  $priority;
            $role->updated_at = date('Y-m-d H:i:s');

            if (!$role->save()) {
                throw new Exception(self::DB_FAILED, 500);
            }

            if (!empty($request->permission)) {
                $role->syncPermissions($request->permission);
            }

            DB::commit();
            return $role;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws Exception
     */
    public function toggleRoleStatus($request): mixed
    {
        DB::beginTransaction();
        try {
            $role = Role::find($request->id);
            if (!$role) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            if(!$this->toggleDataStatus($role)) {
                throw new Exception(self::DB_FAILED, 500);
            }

            DB::commit();
            return $role;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws Exception
     */
    public function delete($request): mixed
    {
        DB::beginTransaction();
        try {

            $role = Role::find($request->id);

            if (!$role) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $role->delete();

            DB::commit();
            return $role;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws Exception
     */
    public function restore($request): mixed
    {
        DB::beginTransaction();
        try {
            $role = Role::onlyTrashed()->find($request->id);

            if (!$role) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $role->restore();

            DB::commit();
            return $role;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws Exception
     */
    public function destroy($request): mixed
    {
        DB::beginTransaction();
        try {
            $role = Role::onlyTrashed()->find($request->id);

            if (!$role) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $role->forceDelete();

            DB::commit();
            return $role;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }
}
