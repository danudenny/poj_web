<?php

namespace App\Services\Core;

use App\Models\Backup;
use Illuminate\Http\JsonResponse;

class BackupService
{
    public function index($request): JsonResponse
    {
        $backups = Backup::query();
        $backups->with(['unit', 'job', 'timesheet', 'asignee', 'backupHistory']);
        $backups->where('unit_id', $request->unit_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $backups->get(),
        ]);
    }
}
