<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\BaseController;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Services\Core\UserService;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * view spesific resource
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function profile(Request $request)
    {
        try {
            $data = $request->user();
            $result = $this->userService->view($data);

            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {

            return $this->sendError($error->getMessage(), [], 500);
        }
    }

    /**
     * Update user profile
     * @param UpdateProfileRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProfileRequest $request)
    {
        try {
            $request['id'] = $request->user()->id;
            $result = $this->userService->update($request);
            return $this->sendSuccess($result, self::SUCCESS_UPDATED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }
}
