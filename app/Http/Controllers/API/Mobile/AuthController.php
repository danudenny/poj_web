<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Core\AuthService;
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
     * @return \Illuminate\Http\Response
     */
    public function login(LoginRequest $request)
    {
        try {
            $data = $request->only(['email', 'password']);
            $data['mobile'] = true;
            $result = $this->authService->login($data);

            return $this->sendSuccess($result, self::LOGIN_SUCCESS);

        } catch (\InvalidArgumentException|\Exception $error) {
            return $this->sendError($error->getMessage());
        }
    }
}
