<?php

namespace App\Http\Controllers;

use App\Http\Requests\Backup\BackupApprovalRequest;
use App\Http\Requests\Backup\BackupCheckInRequest;
use App\Http\Requests\Backup\BackupCheckOutRequest;
use App\Http\Requests\Backup\CreateBackupRequest;
use App\Services\Core\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    private BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->backupService->index($request);
    }

    public function show(Request $request, $id): JsonResponse
    {
        return $this->backupService->show($request, $id);
    }

    public function create(CreateBackupRequest $request): JsonResponse
    {
        return $this->backupService->create($request);
    }

    public function approve(BackupApprovalRequest $request, int $id): JsonResponse
    {
        return $this->backupService->approve($request, $id);
    }

    public function checkIn(BackupCheckInRequest $request, int $id): JsonResponse
    {
        return $this->backupService->checkIn($request, $id);
    }

    public function checkOut(BackupCheckOutRequest $request, int $id): JsonResponse
    {
        return $this->backupService->checkOut($request, $id);
    }

    public function listEmployeeBackupTime(Request $request): JsonResponse {
        return $this->backupService->listEmployeeBackup($request);
    }

    public function getActiveEmployeeEvent(Request $request, int $id): JsonResponse {
        return $this->backupService->getActiveEmployeeDate($request, $id);
    }

    public function getDetailEmployeeBackup(Request $request, int $id) {
        return $this->backupService->detailBackupEmployee($request, $id);
    }

    public function getListApproval(Request $request) {
        return $this->backupService->listApproval($request);
    }

    public function monthlyEvaluate(Request $request) {
        return $this->backupService->monthlyEvaluate($request);
    }

    public function delete($id): JsonResponse
    {
        return $this->backupService->delete($id);
    }
}
