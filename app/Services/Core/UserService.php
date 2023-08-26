<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UserService extends BaseService
{
    private MinioService $minioService;

    public function __construct(MinioService $minioService)
    {
        $this->minioService = $minioService;
    }

    /**
     * @param $data
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = auth()->user();

        try {
            $users = User::query();
            $users->with(['employee', 'employee.job', 'employee.job.roles', 'employee.department']);
            $users->join('employees', 'employees.id', '=', 'users.employee_id');
            $users->select(['users.*']);

            $unitRelationID = $request->get('unit_relation_id');
            $lastUnitRelationID = $request->get('last_unit_id');

            if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

            } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
                if (!$unitRelationID) {
                    $defaultUnitRelationID = $user->employee->unit_id;

                    if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                        $defaultUnitRelationID = $requestUnitRelationID;
                    }

                    $unitRelationID = $defaultUnitRelationID;
                }
            } else if ($this->isRequestedRoleLevel(Role::RoleStaffApproval)) {
                if (!$lastUnitRelationID) {
                    $lastUnitRelationID = $user->employee->unit_id;
                }
            } else {
                $users->where('users.id', '=', $user->id);
            }

            if ($unitRelationID) {
                $users->where(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                        ->orWhere('employees.cabang_id', '=', $unitRelationID)
                        ->orWhere('employees.area_id', '=', $unitRelationID)
                        ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                        ->orWhere('employees.corporate_id', '=', $unitRelationID);
                });
            }

            $users->when(request()->filled('name'), function ($query) {
                $query->whereRaw('LOWER("users.name") LIKE ? ', '%'.strtolower(request()->query('name')).'%');
            });

            $users->when(request()->filled('job_name'), function ($query) {
                $query->whereHas('employee.job', function ($query) {
                    $query->whereRaw('LOWER("name") LIKE ? ', '%'.strtolower(request()->query('job_name')).'%');
                });
            });

            if ($lastUnitRelationID) {
                $users->where('employees.unit_id', '=', $lastUnitRelationID);
            }

            $users->when(request()->filled('email'), function ($query) {
                $query->whereRaw('LOWER("email") LIKE ? ', '%'.strtolower(request()->query('email')).'%');
            });
            $users->when(request()->filled('is_active'), function ($query) {
                $query->where('is_active', '=', request()->query('is_active'));
            });

            $users->when(request()->query('department_id'), function ($query) {
                $query->whereHas('employee', function ($query) {
                    $query->where('department_id', '=', intval(request()->input('department_id')));
                });
            });

            $users->orderBy('users.name');

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => $this->list($users, $request)
            ]);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @param $data
     * @return Model|Builder
     * @throws Exception
     */
    public function view($data): Model|Builder
    {
        try {
            $user = User::with(['roles:id,name'])->firstWhere('id', $data['id']);

            if (!$user) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            $user->append(['is_in_representative_unit', 'is_in_central_unit']);

            return $user;

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
    public function getRoles(): AnonymousResourceCollection
    {
        try {
            $roles = Role::where('is_active', '=', 1)->get();

            if (empty($roles)) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return RoleResource::collection($roles);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
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
                throw new InvalidArgumentException("Failed to sync roles!", 500);
            }

            DB::commit();
            return $user;

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
     * @return Model|Builder
     * @throws Exception
     */
    public function update($request): Model|Builder
    {
        DB::beginTransaction();
        try {
            $user = User::with('employee')->firstWhere('id', $request->id);

            if (!$user) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
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

            $user->employee()->update([
                'team_id' => $request->team_id
            ]);

            if (!$user->save()) {
                throw new Exception(self::DB_FAILED, 500);
            }

            if (!empty($request->roles)) {
                if (!$user->roles()->sync($request->roles)) {
                    throw new InvalidArgumentException("Failed to sync roles!", 500);
                }
            }

            DB::commit();
            return $user;

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

            $user = User::find($request->id);
            if (!$user) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            if(!$this->toggleDataStatus($user)) {
                throw new Exception(self::DB_FAILED, 500);
            }

            DB::commit();
            return $user;

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
            $user = User::find($request->id);
            if (!$user) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $user->delete();

            DB::commit();
            return $user;

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
            $user = User::onlyTrashed()->find($request->id);
            if(!$user) {
                throw new Exception(self::DATA_NOTFOUND, 400);
            }
            $user->restore();

            DB::commit();
            return $user;

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
            $user = User::onlyTrashed()->find($request->id);

            if(!$user) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $user->forceDelete();

            DB::commit();
            return $user;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
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
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
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

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
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
