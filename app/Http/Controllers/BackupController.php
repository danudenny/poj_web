<?php

namespace App\Http\Controllers;

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

    public function create(Request $request): JsonResponse
    {
        return $this->backupService->create($request);
    }
}
