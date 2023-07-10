<?php

namespace App\Services\Core;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PermissionService extends BaseService
{
    public function index($data)
    {
        try {
            $permission = Permission::query();
            $permission->when(request()->filled('name'), function (Builder $query) {
                $query->whereRaw('LOWER(name) like ?', [strtolower('%' . request()->query('name') . '%')]);
            });

            return $this->list($permission, $data);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    public function view($id): PermissionResource
    {
        try {
            $permission = Permission::firstWhere('id', $id);

            if (!$permission) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return PermissionResource::make($permission);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function save($request)
    {
        $permission = Permission::where('name', strtolower(str_replace(' ', '_', $request->name)))->first();
        if ($permission) {
            throw new InvalidArgumentException("name and guard name already exist", 403);
        }

        DB::beginTransaction();
        try {
            $permission = new Permission();
            $permission->name = strtolower(str_replace(' ', '_', $request->name));
            $permission->created_at = date('Y-m-d H:i:s');

            if (!$permission->save()) {
                throw new Exception(self::DB_FAILED, 500);
            }

            DB::commit();
            return $permission;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function update($request)
    {
        $permission = Permission::find($request->id);
        if (!$permission) {
            throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
        }

        $permission = Permission::where('name', strtolower(str_replace(' ', '_', $request->name)))
            ->where('id', '!=', $request->id)
            ->first();
        if ($permission) {
            throw new InvalidArgumentException("name and guard name already exist", 403);
        }

        DB::beginTransaction();
        try {
            $permission->name = strtolower(str_replace(' ', '_', $request->name));
            $permission->updated_at = date('Y-m-d H:i:s');

            if (!$permission->save()) {
                throw new Exception(self::DB_FAILED, 500);
            }

            if (!empty($request->permission)) {
                $permission->syncPermissions($request->permission);
            }

            DB::commit();
            return $permission;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    public function destroy($request)
    {
        DB::beginTransaction();
        try {
            $permission = Permission::find($request->id);

            if (!$permission) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $permission->forceDelete();

            DB::commit();
            return $permission;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }
}
