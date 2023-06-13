<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Setting\SettingSaveRequest;
use App\Http\Requests\SettingUpdateRequest;
use App\Models\Setting;
use App\Services\Core\SettingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use InvalidArgumentException;

class SettingController extends BaseController
{
    private SettingService $settingSvc;

    public function __construct(SettingService $settingSvc) {
        $this->settingSvc = $settingSvc;
    }

    public function index(Setting $setting): JsonResponse
    {
        try {
            $result = $this->settingSvc->index($setting);
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (Exception | InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function save(SettingSaveRequest $request): JsonResponse
    {
        try {
            $result = $this->settingSvc->save($request);
            return $this->sendSuccess($result, self::SUCCESS_CREATED, 201);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function update(SettingUpdateRequest $request, $id): JsonResponse
    {
        try {
            $result = $this->settingSvc->update($request, $id);
            return $this->sendSuccess($result, self::SUCCESS_UPDATED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function delete($id): JsonResponse
    {
        try {
            $result = $this->settingSvc->delete($id);
            return $this->sendSuccess($result, self::SUCCESS_DELETED);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }
}
