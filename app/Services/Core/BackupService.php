<?php

namespace App\Services\Core;

use App\Models\Backup;
use App\Models\BackupHistory;
use App\Models\Employee;
use App\Models\EmployeeTimesheet;
use App\Models\Job;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BackupService
{
    public function index($request): JsonResponse
    {
        $backups = Backup::query();
        $backups->with(['unit', 'job', 'timesheet', 'assignee', 'backupHistory']);
        $backups->where('unit_id', $request->unit_id);
        $backups->orderBy('created_at', 'desc');

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $backups->get(),
        ]);
    }

    public function show($request, $id): JsonResponse
    {
        $unitIdExists = Unit::find($request->unit_id);
        if (!$unitIdExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit id not found',
            ], 404);
        }

        $backup = Backup::query();
        $backup->with(['unit', 'job', 'timesheet', 'assignee', 'backupHistory']);
        $backup->where('unit_id', $request->unit_id);
        $backup->where('id', $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $backup->first(),
        ]);
    }

    public function create($request) {
        $unitExists = Unit::find($request->unit_id);
        if (!$unitExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit id not found',
            ], 404);
        }

        $jobExists = Job::find($request->job_id);
        if (!$jobExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job id not found',
            ], 404);
        }

        $employeeExists = Employee::where('unit_id', $request->unit_id)->find($request->assignee_id);
        if (!$employeeExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee id not found',
            ], 404);
        }

        $backupExists = Backup::leftJoin('backup_histories', 'backups.id', '=', 'backup_histories.backup_id')
            ->where('assignee_id', $request->assignee_id)
            ->where('backup_histories.status', 'assigned')
            ->orWhere('backup_histories.status', 'in_progress')
            ->first();

        if ($backupExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Backup already exists',
            ], 400);
        }

        if ($request->shift_type === 'Shift') {
            $timesheet = EmployeeTimesheet::find($request->timesheet_id);
            if (!$timesheet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Timesheet id not found',
                ], 404);
            }
        }

        $startDate = date_create($request->start_date);
        $endDate = date_create($request->end_date);
        $diff = date_diff($startDate, $endDate);
        $diffInDays = $diff->format("%a");
        if (intval($diffInDays) !== $request->duration) {
            return response()->json([
                'status' => 'error',
                'message' => 'Duration is not in start_date and end_date difference',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $backup = Backup::create([
                'unit_id' => $request->unit_id,
                'job_id' => $request->job_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'timesheet_id' => $request->shift_type === 'Shift' ? $request->timesheet_id : null,
                'assignee_id' => $request->assignee_id,
                'shift_type' => $request->shift_type,
                'duration' => $request->duration,
            ]);

            $backupHistory = BackupHistory::create([
                'backup_id' => $backup->id,
                'status' => 'assigned',
            ]);

            $backup->save();
            $backupHistory->save();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success create backup',
                'data' => $backup,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
 }
