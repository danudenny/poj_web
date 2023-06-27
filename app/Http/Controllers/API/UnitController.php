<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Services\Core\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends BaseController
{
    private UnitService $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->unitService->index($request);
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    /**
     * view specific resource
     * @param Request $request
     * @return JsonResponse
     */
    public function view(Request $request): JsonResponse
    {
        try {
            $data = $request->only('id');
            $result = $this->unitService->view($data);

            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (\Exception | \InvalidArgumentException $error) {

            return $this->sendError($error->getMessage(), [], 500);
        }
    }

}
