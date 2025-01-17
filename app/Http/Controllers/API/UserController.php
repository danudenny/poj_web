<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\User\ChangeProfilePicture;
use App\Http\Requests\User\UserSaveRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\UserMobileResource;
use App\Services\Core\UserService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            return $this->userService->index($request);
        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * view spesific resource
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request)
    {
        try {
            $data = $request->only('id');
            $result = $this->userService->view($data);

            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {

            return $this->sendError($error->getMessage(), [], 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function getRoles()
    {
        try {
            $result = $this->userService->getRoles();

            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {

            return $this->sendError($error->getMessage(), [], 500);
        }
    }

    /**
     * store resource
     * @param UserSaveRequest $request
     * @return \Illuminate\Http\Response
     */
    public function save(UserSaveRequest $request)
    {
        try {
            $result = $this->userService->save($request);
            return $this->sendSuccess($result, self::SUCCESS_CREATED, 201);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Update spesific resource
     * @param UserUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(UserUpdateRequest $request)
    {
        try {
            $result = $this->userService->update($request);
            return $this->sendSuccess($result, self::SUCCESS_UPDATED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleRoleStatus(Request $request): JsonResponse
    {
        try {
            $result = $this->userService->toggleRoleStatus($request);
            return $this->sendSuccess($result, self::SUCCESS_UPDATED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function updateAvatar(Request $request, $id)
    {
        try {
            $result = $this->userService->updateAvatar($request, $id);
            return $this->sendSuccess($result, self::SUCCESS_UPDATED. ' '. 'profile image');

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Softdelete data user
     * @param Request $request
     * @return \Illuminate\Http\Response|void
     */
    public function delete(Request $request)
    {
        try {
            $result = $this->userService->delete($request);
            return $this->sendSuccess($result, self::SUCCESS_DELETED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Restore data user
     * @param Request $request
     * @return \Illuminate\Http\Response|void
     */
    public function restore(Request $request)
    {
        try {
            $result = $this->userService->restore($request);
            return $this->sendSuccess($result, self::SUCCESS_RESTORE);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * Destroy data user
     * @param Request $request
     * @return \Illuminate\Http\Response|void
     */
    public function destroy(Request $request)
    {
        try {
            $result = $this->userService->destroy($request);
            return $this->sendSuccess($result, self::SUCCESS_DESTROYED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function updateToken(Request $request, $id): JsonResponse
    {
        return $this->userService->updateToken($request, $id);
    }

    public function profile(): JsonResponse
    {
        return $this->userService->profile();
    }

    public function changePassword(ChangePasswordRequest $request) {
        return $this->userService->changePassword($request);
    }

    public function changeProfile(ChangeProfilePicture $request) {
        return $this->userService->changeProfilePicture($request);
    }

    public function profileV2(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => UserMobileResource::make($user)
        ]);
    }

    public function resetInitialFace(Request $request, int $userID) {
        return $this->userService->resetInitialFace($request, $userID);
    }

    public function changeUserInitialFaceURL(Request $request) {
        if ($request->input('token') != 'poj-alakad-12115ASD324FG') {
            return view('welcome');
        }

        return $this->userService->changeUserInitialFaceURL($request);
    }
}
