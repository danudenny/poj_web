<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrection\ApprovalCorrectionRequest;
use App\Http\Requests\AttendanceCorrection\CreateCorrectionRequest;
use App\Services\Core\AttendanceCorrectionService;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    private AttendanceCorrectionService $service;

    public function __construct(AttendanceCorrectionService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request) {
        return $this->service->index($request);
    }

    public function view(Request $request, int $id) {
        return $this->service->view($request, $id);
    }

    public function listApproval(Request $request) {
        return $this->service->listApproval($request);
    }

    public function create(CreateCorrectionRequest $request) {
        return $this->service->createRequest($request);
    }

    public function approval(ApprovalCorrectionRequest $request, int $id) {
        return $this->service->approval($request, $id);
    }
}
