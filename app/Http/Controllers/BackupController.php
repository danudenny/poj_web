<?php

namespace App\Http\Controllers;

use App\Services\Core\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    private BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function index(Request $request) {
        return $this->backupService->index($request);
    }
}
