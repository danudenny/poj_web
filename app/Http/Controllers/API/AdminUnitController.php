<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUnit\CreateAdminUnitRequest;
use App\Http\Requests\AdminUnit\RemoveAdminUnitRequest;
use App\Services\Core\AdminUnitService;
use Illuminate\Http\Request;

class AdminUnitController extends Controller
{
    /**
     * @param Request $request
     * @param AdminUnitService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, AdminUnitService $service) {
        return $service->index($request);
    }

    /**
     * @param CreateAdminUnitRequest $request
     * @param AdminUnitService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateAdminUnitRequest $request, AdminUnitService $service) {
        return $service->insert($request);
    }

    /**
     * @param RemoveAdminUnitRequest $request
     * @param AdminUnitService $service
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(RemoveAdminUnitRequest $request, AdminUnitService $service, int $id) {
        return $service->remove($request, $id);
    }

    public function myAdminUnits(Request $request, AdminUnitService $service) {
        return $service->myAdminUnit($request);
    }
}
