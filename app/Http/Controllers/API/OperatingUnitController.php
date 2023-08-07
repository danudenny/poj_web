<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\OperatingUnit\AssignOperatingUnitRequest;
use App\Http\Requests\OperatingUnit\AssignUserRequest;
use App\Http\Requests\OperatingUnit\RemoveOperatingUnitRequest;
use App\Http\Requests\OperatingUnit\RemoveUserRequest;
use App\Services\Core\OperatingUnitService;
use Illuminate\Http\Request;

class OperatingUnitController extends Controller
{
    public function index(Request $request, OperatingUnitService $service) {
        return $service->index($request);
    }

    public function kanwil(Request $request, OperatingUnitService $service) {
        return $service->kanwils($request);
    }

    public function corporate(Request $request, OperatingUnitService $service) {
        return $service->corporates($request);
    }

    public function availableKanwil(Request $request, OperatingUnitService $service) {
        return $service->availableKanwil($request);
    }

    public function assign(AssignOperatingUnitRequest $request, OperatingUnitService $service) {
        return $service->assign($request);
    }

    public function remove(RemoveOperatingUnitRequest $request, OperatingUnitService $service, int $id) {
        return $service->removeOperatingUnit($request, $id);
    }

    public function assignUser(AssignUserRequest $request, OperatingUnitService $service) {
        return $service->assignUser($request);
    }

    public function removeUser(RemoveUserRequest $request, OperatingUnitService $service) {
        return $service->removeUser($request);
    }
}
