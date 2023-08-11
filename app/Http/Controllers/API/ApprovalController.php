<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\CreateApprovalRequest;
use App\Http\Requests\Approval\UpdateApprovalRequest;
use App\Services\Core\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    private ApprovalService $approvalSvc;

    public function __construct(ApprovalService $approvalSvc) {
        $this->approvalSvc = $approvalSvc;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->approvalSvc->index($request);
    }

    public function show($id): JsonResponse
    {
        return $this->approvalSvc->show($id);
    }

    public function save(CreateApprovalRequest $request): JsonResponse
    {
        return $this->approvalSvc->save($request);
    }

    public function update(UpdateApprovalRequest $request, int $id): JsonResponse
    {
        return $this->approvalSvc->update($request, $id);
    }

    public function delete($id): JsonResponse
    {
        return $this->approvalSvc->delete($id);
    }

    public function getOrg(Request $request): JsonResponse
    {
        return $this->approvalSvc->getOrg($request->id);
    }
}
