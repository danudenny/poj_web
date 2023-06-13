<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Core\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $request->only(['email', 'password']);
            $data['mobile'] = false;
            $result = $this->authService->login($data);

            return $this->sendSuccess($result, self::LOGIN_SUCCESS);

        } catch (\InvalidArgumentException|\Exception $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function login_with_auth_key(Request $request)
    {
        try {
            $data = $request->only('authkey');
            $result = $this->authService->login_with_auth_key($data);

            return $this->sendSuccess($result, self::LOGIN_SUCCESS);

        } catch (\InvalidArgumentException|\Exception $error) {

            return $this->sendError($error->getMessage());
        }
    }

    /**
     * logout and revoke token
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        return $this->sendSuccess($user,'Token revoked');
    }

    /**
     * Fetching User Permissions
     * @return \Illuminate\Http\Response
     *
     */
    public function permission()
    {
        try {
            $result = $this->authService->permission();
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\InvalidArgumentException|\Exception $error) {

            return $this->sendError($error->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function forget_password(Request $request)
    {
        try {
            $data = $request->only('email');
            $result = $this->authService->forget_password($data);

            return $this->sendSuccess($result, 'Token ready');

        }
        catch (\InvalidArgumentException|\Exception $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function has_token(Request $request)
    {
        try {
            $data = $request->only('token');
            $result = $this->authService->has_token($data);

            return $this->sendSuccess($result,
                self::SUCCESS_FETCH);

        } catch (\InvalidArgumentException|\Exception $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function reset_password(Request $request)
    {
        try {
            $data = $request->only(['email', 'password']);
            $result = $this->authService->reset_password($data);

            return $this->sendSuccess($result, self::SUCCESS_UPDATED);

        } catch (\InvalidArgumentException|\Exception $error) {
            return $this->sendError($error->getMessage());
        }
    }
}
