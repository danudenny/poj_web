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
            return $this->unitService->index($request);

        } catch (\Exception | \InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }
    }

    public function paginatedListUnits(Request $request): JsonResponse {
        return $this->unitService->paginatedListUnit($request);
    }

    /**
     * view specific resource
     * @param Request $request
     * @return JsonResponse
     */
    public function view(Request $request, $id): JsonResponse
    {
        try {
            return $this->unitService->view($request, $id);

        } catch (\Exception | \InvalidArgumentException $error) {

            return $this->sendError($error->getMessage(), [], 500);
        }
    }

    public function allUnitNoFilter(Request $request) {
        return $this->unitService->allUnitNoFilter($request);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->unitService->update($request, $id);
    }

}
