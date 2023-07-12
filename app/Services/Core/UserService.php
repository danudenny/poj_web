<?php

namespace App\Services\Core;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use App\Services\MinioService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    private MinioService $minioService;

    public function __construct(MinioService $minioService)
    {
        $this->minioService = $minioService;
    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function index($data): mixed
    {
        try {
            $users = User::query();
            $users->with(['roles:name', 'employee']);
            $users->when(request()->filled('name'), function ($query) {
                $query->whereRaw('LOWER("name") LIKE ? ', '%'.strtolower(request()->query('name')).'%');
            });
            $users->when(request()->filled('unit_id'), function ($query) {
                $query->whereHas('employee', function ($query) {
                    $query->where('unit_id', '=', request()->query('unit_id'));
                });
            });
            $users->when(request()->filled('email'), function ($query) {
                $query->whereRaw('LOWER("email") LIKE ? ', '%'.strtolower(request()->query('email')).'%');
            });
            $users->when(request()->filled('is_active'), function ($query) {
                $query->where('is_active', '=', request()->query('is_active'));
            });
            $users->orderBy('name', 'asc');

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => $users->paginate($data->per_page ?? 10)
            ], 200);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function view($data): mixed
    {
        try {
            $user = User::with(['roles:id,name'])->firstWhere('id', $data['id']);

            if (!$user) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return $user;

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws Exception
     */
    public function getRoles()
    {
        try {
            $roles = Role::where('is_active', '=', 1)->get();

            if (empty($roles)) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return RoleResource::collection($roles);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @param $request
     * @return User
     * @throws Exception
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
            $user->username = $request->username;
            $user->employee_id = $request->employee_id;
            $user->created_at = date('Y-m-d H:i:s');

            if (!$user->save()) {
                throw new Exception(self::DB_FAILED, 500);
            }

            if(!$user->roles()->sync($request->roles)){
                throw new \InvalidArgumentException("Failed to sync roles!", 500);
            }

            DB::commit();
            return $user;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
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
            $user->username = $request->username;
            $user->employee_id = $request->employee_id;
            $user->updated_at = date('Y-m-d H:i:s');

            if (!$user->save()) {
                throw new Exception(self::DB_FAILED, 500);
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

            $user = User::find($request->id);
            if (!$user) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            if(!$this->toggleDataStatus($user)) {
                throw new Exception(self::DB_FAILED, 500);
            }

            DB::commit();
            return $user;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
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
            $user = User::onlyTrashed()->find($request->id);
            if(!$user) {
                throw new Exception(self::DATA_NOTFOUND, 400);
            }
            $user->restore();

            DB::commit();
            return $user;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
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
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * Update user profile image / avatar
     * @param $request
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function updateAvatar($request, $id) {
        DB::beginTransaction();
        try {
            $user = User::firstWhere('id', $id);

            if (!$user) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            $path = 'uploads/avatar';
            $uploadedPath = $this->minioService->uploadFile($this->fromBase64($request->avatar), $path);

            $user->avatar = $uploadedPath;
            $user->updated_at = date('Y-m-d H:i:s');

            if (!$user->save()) {
                throw new Exception(self::DB_FAILED, 500);
            }

            DB::commit();
            return $user;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    public function updateToken($request, $id): JsonResponse
    {
        $userExists = User::where('id', $id)->exists();
        if(!$userExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        try {
            $user = User::find($id);
            $user->fcm_token = $request->fcm_token;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Token updated successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update token'
            ], 500);
        }
    }
}
