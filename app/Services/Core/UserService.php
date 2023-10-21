<?php

namespace App\Services\Core;

use App\Helpers\Notification\MailNotification;
use App\Helpers\UnitHelper;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\User\ChangeProfilePicture;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserProfileCollection;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserRolePermissionResource;
use App\Mail\PasswordResetMail;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use App\Services\MinioService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

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
            $users->with(['employee', 'employee.job', 'roles', 'employee.department', 'allowedOperatingUnits']);
            $users->join('employees', 'employees.id', '=', 'users.employee_id');
            $users->select(['users.*']);

            $unitRelationID = $request->get('unit_relation_id');
            $lastUnitRelationID = $request->get('last_unit_id');

            if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

            } else if ($this->isRequestedRoleLevel(Role::RoleAdminUnit)) {
                if (!$unitRelationID) {
                    $defaultUnitRelationID = $user->employee->unit_id;

                    if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                        $defaultUnitRelationID = $requestUnitRelationID;
                    }

                    $unitRelationID = $defaultUnitRelationID;
                }
            } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
                $users->leftJoin('user_operating_units', 'user_operating_units.unit_relation_id', '=', 'employees.default_operating_unit_id');
                $users->where(function (Builder $builder) use ($user) {
                    $builder->orWhere('user_operating_units.user_id', '=', $user->id);
                });
            } else {
                $subQuery = "(
                            WITH RECURSIVE job_data AS (
                                SELECT * FROM unit_has_jobs
                                WHERE unit_relation_id = '{$user->employee->unit_id}' AND odoo_job_id = {$user->employee->job_id}
                                UNION ALL
                                SELECT uj.* FROM unit_has_jobs uj
                                INNER JOIN job_data jd ON jd.id = uj.parent_unit_job_id
                            )
                            SELECT * FROM job_data
                        ) relatedJob";
                $users->join(DB::raw($subQuery), function (JoinClause $joinClause) {
                    $joinClause->on(DB::raw("relatedJob.odoo_job_id"), '=', DB::raw('employees.job_id'))
                        ->where(DB::raw("relatedJob.unit_relation_id"), '=', DB::raw('employees.unit_id'));
                });

                $users->where(function (Builder $builder) use ($user) {
                    $builder->orWhere(function(Builder $builder) use ($user) {
                        $builder->where('employees.job_id', '=', $user->employee->job_id)
                            ->where('employees.unit_id', '=', $user->employee->unit_id)
                            ->where('employees.id', '=', $user->employee_id);
                    })->orWhere(function (Builder $builder) use ($user) {
                        $builder->orWhere('employees.job_id', '!=', $user->employee->job_id)
                            ->orWhere('employees.unit_id', '!=', $user->employee->unit_id);
                    });
                });
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
                $query->whereRaw('LOWER(users.name) LIKE ? ', '%'.strtolower(request()->query('name')).'%');
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
            $user = User::with(['roles:id,name', 'employee', 'allowedOperatingUnits'])->firstWhere('id', $data['id']);

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

            $allowedOperatingUnits = $request->input('allowed_operating_units', []);

            UserOperatingUnit::query()
                ->where('user_id', '=', $user->id)
                ->delete();

            foreach ($allowedOperatingUnits as $allowedOperatingUnit) {
                $userOperatingUnit = new UserOperatingUnit();
                $userOperatingUnit->user_id = $user->id;
                $userOperatingUnit->unit_relation_id = $allowedOperatingUnit;
                $userOperatingUnit->save();
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

    public function changePassword(ChangePasswordRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $data = [
                'old_password' => $request->input('old_password'),
                'new_password' => $request->input('new_password'),
                'confirmation_password' => $request->input('confirmation_password')
            ];

            if ($data['new_password'] != $data['confirmation_password']) {
                return response()->json([
                    'status' => false,
                    'message' => 'Confirmation password not same'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if (!Hash::check($data['old_password'], $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Old password invalid'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $user->password = Hash::make($data['new_password']);
            $user->authkey = Hash::make($user->email.'-'.$user->password);
            $user->is_password_changed = true;
            $user->save();

            MailNotification::SendMailable($user->email, new PasswordResetMail([
                'fullname' => $user->name,
                'email' => $user->email,
                'new_password' => $data['new_password'],
            ]));

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function changeProfilePicture(ChangeProfilePicture $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            DB::beginTransaction();
            $user->avatar = $request->input('image_url');
            $user->save();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sukses!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resetInitialFace(Request $request, int $userID) {
        try {
            if (!$this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki akses!'
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            /**
             * @var User $user
             */
            $user = User::query()->where('id', '=', $userID)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();
            $user->is_new = true;
            $user->save();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sukses!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function changeUserInitialFaceURL(Request $request) {
        try {
            $employeeID = $request->input('employee_id');
            $image = $request->file('image');

            if (!$employeeID) {
                return response()->json([
                    'status' => false,
                    'message' => "User is empty"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if (!$image) {
                return response()->json([
                    'status' => false,
                    'message' => "Image is empty"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var User $user
             */
            $user = User::query()->where('employee_id', '=', $employeeID)->first();
            if(!$user) {
                return response()->json([
                    'status' => false,
                    'message' => "User is not found"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $path = 'face_initial/' . $user->name;
            $fullFilePath = $path . '/'. uniqid() .'_'. $image->getClientOriginalName();
            $isSuccess = Storage::disk('s3')->put($fullFilePath, file_get_contents($image));
            if(!$isSuccess) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed upload image'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }
            $path = Storage::disk('s3')->url($fullFilePath);

            $user->face_initial_url = $path;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Sukses!'
            ]);
        } catch (\Throwable $exception) {

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
