<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Services\Core\ApprovalModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalModuleController extends BaseController
{
    private ApprovalModuleService $approvalModuleSvc;

    public function __construct(ApprovalModuleService $approvalModuleSvc) {
        $this->approvalModuleSvc = $approvalModuleSvc;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->approvalModuleSvc->index($request);
    }

    public function save(Request $request): JsonResponse
    {
        return $this->approvalModuleSvc->save($request);
    }

    public function show($id): JsonResponse
    {
        return $this->approvalModuleSvc->show($id);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->approvalModuleSvc->update($request, $id);
    }

    public function delete($id): JsonResponse
    {
        return $this->approvalModuleSvc->delete($id);
    }
}
