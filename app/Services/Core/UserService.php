<?php

namespace App\Services\Core;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    /**
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function index($data): mixed
    {
        try {
            $users = User::query()->with(['roles']);
            if (!is_null($data->name)) {
                $users->where('name', 'like', '%' . $data->name . '%');
            }
            if (!is_null($data->email)) {
                $users->where('email', 'like', '%' . $data->email . '%');
            }

            return $this->list($users, $data);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function view($data): mixed
    {
        try {
            $user = User::firstWhere('id', $data['id']);

            if (!$user) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return $user;

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $request
     * @return User
     * @throws \Exception
     */
    public function save($request): User
    {
        DB::beginTransaction();
        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->authkey = Hash::make($request->email.'-'.$request->password);
            $user->created_at = date('Y-m-d H:i:s');

            if (!$user->save()) {
                throw new \Exception(self::DB_FAILED, 500);
            }

            if(!$user->roles()->sync($request->roles)){
                throw new \InvalidArgumentException("Failed to sync roles!", 500);
            }

            DB::commit();
            return $user;

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
            $user = User::firstWhere('id', $request->id);

            if (!$user) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            if (!empty($request->password)) {
                $user->password = Hash::make($request->password);
                $user->authkey = Hash::make($request->email.'-'.$request->password);
            }

            $user->name = $request->name;
            $user->email = $request->email;
            $user->updated_at = date('Y-m-d H:i:s');

            if (!$user->save()) {
                throw new \Exception(self::DB_FAILED, 500);
            }

            if (!empty($request->roles)) {
                if (!$user->roles()->sync($request->roles)) {
                    throw new \InvalidArgumentException("Failed to sync roles!", 500);
                }
            }

            DB::commit();
            return $user;

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
            $user = User::find($request->id);
            if (!$user) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $user->delete();

            DB::commit();
            return $user;

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
            $user = User::onlyTrashed()->find($request->id);
            if(!$user) {
                throw new \Exception(self::DATA_NOTFOUND, 400);
            }
            $user->restore();

            DB::commit();
            return $user;

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
            $user = User::onlyTrashed()->find($request->id);

            if(!$user) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $user->forceDelete();

            DB::commit();
            return $user;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }
}
