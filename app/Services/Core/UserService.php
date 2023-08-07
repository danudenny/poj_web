<?php

namespace App\Services\Core;

use App\Http\Resources\RoleResource;
use App\Http\Resources\UserProfileCollection;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserRolePermissionResource;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use App\Services\MinioService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    private MinioService $minioService;
    private JobService $jobService;

    public function __construct(MinioService $minioService, JobService $jobService)
    {
        $this->minioService = $minioService;
        $this->jobService = $jobService;
    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function index($data): mixed
    {
        $lastUnit = auth()->user()->employee->last_unit;


        $roles = auth()->user()->roles->sortBy('priority')->first();
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
            $users->when(request()->filled('last_unit_id'), function (Builder $query) {
                $query->leftJoin('employees', 'employees.id', '=', 'users.employee_id');
                $query->select(['users.*']);

                $lastUnitRelationID = request()->input('last_unit_id');

                $query->where(function($builder) use ($lastUnitRelationID) {
                        $builder->orWhere(function($builder) use ($lastUnitRelationID) {
                            $builder->where('employees.outlet_id', '=', $lastUnitRelationID);
                        })
                        ->orWhere(function($builder) use ($lastUnitRelationID) {
                            $builder->where('employees.outlet_id', '=', 0)
                                ->where('employees.cabang_id', '=', $lastUnitRelationID);
                        })
                        ->orWhere(function($builder) use ($lastUnitRelationID) {
                            $builder->where('employees.outlet_id', '=', 0)
                                ->where('employees.cabang_id', '=', 0)
                                ->where('employees.area_id', '=', $lastUnitRelationID);
                        })
                        ->orWhere(function($builder) use ($lastUnitRelationID) {
                            $builder->where('employees.outlet_id', '=', 0)
                                ->where('employees.cabang_id', '=', 0)
                                ->where('employees.area_id', '=', 0)
                                ->where('employees.kanwil_id', '=', $lastUnitRelationID);
                        })
                        ->orWhere(function($builder) use ($lastUnitRelationID) {
                            $builder->where('employees.outlet_id', '=', 0)
                                ->where('employees.cabang_id', '=', 0)
                                ->where('employees.area_id', '=', 0)
                                ->where('employees.kanwil_id', '=', 0)
                                ->where('employees.corporate_id', '=', $lastUnitRelationID);
                        });
                });
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

            $user->append('is_in_representative_unit');

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

    public function profile() {
        $auth = auth()->user();
        $user = User::with(['roles', 'employee'])->find($auth->id);

        return response()->json([
            'status' => 'success',
            'message' => 'User profile fetched successfully',
            'data' => UserRolePermissionResource::make($user)
        ], 200);
    }
}
