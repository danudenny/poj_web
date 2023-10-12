<?php

namespace App\Services\Core;

use App\Helpers\Notification\MailNotification;
use App\Http\Requests\Auth\ForgetPasswordRequest;
use App\Http\Resources\UserRolePermissionResource;
use App\Http\Resources\UserMobileResource;
use App\Http\Resources\UserResource;
use App\Jobs\PasswordResetMailJob;
use App\Mail\PasswordResetMail;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthService extends BaseService
{
    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function login(array $data): array
    {
        try {

            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                throw new \InvalidArgumentException(self::NOT_REGISTERED, 401);
            }

//            $credentials['email'] = $user->email;
//            $credentials['password'] = $data['password'];
//
//            if (!Auth::attempt($credentials)) {
//                throw new \InvalidArgumentException(self::AUTH_FAILED, 401);
//            }
//
//            if (!Hash::check($data['password'], $user->password, [])) {
//                throw new \InvalidArgumentException("Email or password doesn't match", 400);
//            }

            $token = $user->createToken('authToken')->plainTextToken;
            if (!$data['mobile']) {
                $user = UserMobileResource::make($user);
            } else {
                $user = UserMobileResource::make($user);
            }

            return [
                'token' => $token,
                'user' => $user
            ];

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG . " : " . $e->getMessage());
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function login_with_auth_key(array $data): array
    {
        try {
            $this->validateData($data, [
                'authkey' => 'required',
            ]);

            $user = User::with(['roles.permissions'])
                ->where('authkey', urldecode($data['authkey']))
                ->first();
            if (!$user) {
                throw new \InvalidArgumentException(self::AUTH_FAILED, 401);
            }

            $token = $user->createToken('authToken')->plainTextToken;
            $user = UserResource::make($user);

            return [
                'token' => $token,
                'user' => $user
            ];

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG . " : " . $e->getMessage());
        }
    }

    /**
     * @return UserResource
     * @throws \Exception
     */
    public function permission(): UserRolePermissionResource
    {
        try {
            $user_id = Auth::user()->id;
            $user = User::with(['roles.permissions'])
                ->where('id', $user_id)->first();

            if (!$user) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            return UserRolePermissionResource::make($user);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG . " : " . $e->getMessage());
        }
    }

    public function forget_password(ForgetPasswordRequest $data)
    {
        try {
            DB::beginTransaction();

            /**
             * @var User $user
             */
            $user = User::firstWhere('email', $data->input('email'));
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $newPassword = strtoupper(Str::random(6));
            $user->password = Hash::make($newPassword);
            $user->save();

            DB::commit();

            MailNotification::SendMailable($user->email, new PasswordResetMail([
                'fullname' => $user->name,
                'email' => $user->email,
                'new_password' => $newPassword,
            ]));

            return response()->json([
                'status' => true,
                'message' => 'Success',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function has_token(array $data): mixed
    {
        try {
            $password_reset = PasswordReset::where('token', $data['token'])->first();

            if (!$password_reset) {
                throw new \InvalidArgumentException("Link has expired", 400);
            }
            return $password_reset->email;

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG . " : " . $e->getMessage());
        }
    }

    /**
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function reset_password(array $data): mixed
    {
        DB::beginTransaction();
        try {
            $this->validateData($data, [
                'token' => 'string',
                'password' => 'required',
                'confirmationPassword' => 'same:password'
            ]);

            $dataToken = $this->has_token(['token' => $data['token']]);

            if (!$dataToken) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            $user = User::where('email', $dataToken)->first();
            $user->password = Hash::make($data['password']);
            $user->updated_at = date('Y-m-d H:i:s');

            if (!$user->save()) {
                throw new \InvalidArgumentException(self::DB_FAILED, 500);
            }

            DB::table('password_resets')->where('email', $dataToken)->delete();
            DB::commit();

            return $user;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG . " : " . $e->getMessage());
        }
    }
}
