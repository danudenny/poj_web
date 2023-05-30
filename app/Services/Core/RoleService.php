<?php

namespace App\Services\Core;

use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class RoleService extends BaseService
{
    /**
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function index($data): mixed
    {
        try {
            $roles = Role::query();
            if (!is_null($data['name'])) {
                $roles->where('name', 'like', '%' . $data['name'] . '%');
            }
            if (!is_null($data['is_active'])) {
                $roles->where('is_active', '=', $data['is_active']);
            }
            return $this->list($roles, $data);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $id
     * @return RoleResource
     * @throws \Exception
     */
    public function view($id): RoleResource
    {
        try {
            $role = Role::with('permissions')->firstWhere('id', $id);

            if (!$role) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return RoleResource::make($role);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @return PermissionResource
     * @throws \Exception
     */
    public function getPermissions()
    {
        try {
            $permissions = Permission::get();

            if (empty($permissions)) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return PermissionResource::collection($permissions);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @param $request
     * @return Role
     * @throws \Exception
     */
    public function save($request): Role
    {
        DB::beginTransaction();
        try {
            $role = new Role();
            $role->name = $request->name;
            $role->created_at = date('Y-m-d H:i:s');

            if (!$role->save()) {
                throw new \Exception(self::DB_FAILED, 500);
            }

            if (!empty($request->permission)) {
                $role->syncPermissions($request->permission);
            }

            DB::commit();
            return $role;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function update($request): mixed
    {
        DB::beginTransaction();
        try {

            $role = Role::find($request->id);
            if (!$role) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            $role->name = $request->name;
            $role->updated_at = date('Y-m-d H:i:s');

            if (!$role->save()) {
                throw new \Exception(self::DB_FAILED, 500);
            }

            if (!empty($request->permission)) {
                $role->syncPermissions($request->permission);
            }

            DB::commit();
            return $role;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function toggleRoleStatus($request): mixed
    {
        DB::beginTransaction();
        try {

            $role = Role::find($request->id);
            if (!$role) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            if(!$this->toggleDataStatus($role)) {
                throw new \Exception(self::DB_FAILED, 500);
            }

            DB::commit();
            return $role;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function delete($request): mixed
    {
        DB::beginTransaction();
        try {

            $role = Role::find($request->id);

            if (!$role) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $role->delete();

            DB::commit();
            return $role;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function restore($request): mixed
    {
        DB::beginTransaction();
        try {
            $role = Role::onlyTrashed()->find($request->id);

            if (!$role) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $role->restore();

            DB::commit();
            return $role;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function destroy($request): mixed
    {
        DB::beginTransaction();
        try {
            $role = Role::onlyTrashed()->find($request->id);

            if (!$role) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $role->forceDelete();

            DB::commit();
            return $role;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }
}
