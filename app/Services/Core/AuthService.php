<?php

namespace App\Services\Core;

use App\Http\Resources\UserRolePermissionResource;
use App\Http\Resources\UserMobileResource;
use App\Http\Resources\UserResource;
use App\Jobs\PasswordResetMailJob;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

            $credentials['email'] = $user->email;
            $credentials['password'] = $data['password'];

            if (!Auth::attempt($credentials)) {
                throw new \InvalidArgumentException(self::AUTH_FAILED, 401);
            }

            if (!Hash::check($data['password'], $user->password, [])) {
                throw new \InvalidArgumentException("Email or password doesn't match", 400);
            }

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

    /**
     * @param array $data
     * @return true
     * @throws \Exception
     */
    public function forget_password(array $data): bool
    {
        DB::beginTransaction();
        try {
            $this->validateData($data, [
                'email' => 'required'
            ]);

            $user = User::firstWhere('email', $data['email']);
            if (!$user) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 401);
            }

            $password_reset = [
                'email' => $user->email,
                'token' => Str::random(),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            PasswordReset::updateOrCreate(['email' => $user->email], $password_reset);

            $baseUrl = config('app.url');
            $templateData['email'] = $password_reset['email'];
            $templateData['token'] = $password_reset['token'];
            $templateData['markdown'] = 'emails.password-reset';
            $templateData['link'] =  $baseUrl. '/auth/reset_password?token=' . $templateData['token'];
            dispatch(new PasswordResetMailJob($templateData, $templateData['email']));

            DB::commit();
            return true;

        }
        catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        }
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG . " : " . $e->getMessage());
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
