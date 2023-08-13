<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Services\Core\SettingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends BaseController
{
    private SettingService $settingSvc;

    public function __construct(SettingService $settingSvc) {
        $this->settingSvc = $settingSvc;
    }

    /**
     * @throws Exception
     */
    public function index(): JsonResponse
    {
        return $this->settingSvc->index();
    }

    /**
     * @throws Exception
     */
    public function save(Request $request): JsonResponse
    {
       return $this->settingSvc->save($request);
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, $id): JsonResponse
    {
       return $this->settingSvc->update($request, $id);
    }

    /**
     * @throws Exception
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        return $this->settingSvc->bulkUpdate($request);
    }

    /**
     * @throws Exception
     */
    public function delete($id): JsonResponse
    {
        return $this->settingSvc->delete($id);
    }
}
