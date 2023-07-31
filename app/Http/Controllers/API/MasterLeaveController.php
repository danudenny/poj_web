<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Core\MasterLeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterLeaveController extends Controller
{
    private MasterLeaveService $masterLeaveService;

    public function __construct(MasterLeaveService $masterLeaveService)
    {
        $this->masterLeaveService = $masterLeaveService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->masterLeaveService->index($request);
    }

    public function show($id): JsonResponse
    {
        return $this->masterLeaveService->show($id);
    }

    public function save(Request $request): JsonResponse
    {
        return $this->masterLeaveService->save($request);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->masterLeaveService->update($request, $id);
    }

    public function delete($id): JsonResponse
    {
        return $this->masterLeaveService->delete($id);
    }
}
