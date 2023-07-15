<?php

namespace App\Services\Core;

use App\Models\Backup;
use App\Models\BackupHistory;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceHistory;
use App\Models\EmployeeTimesheet;
use App\Models\Job;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\AssignBackupRequestNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class BackupService
{
    public function index(Request $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        $backups = Backup::query();
        if (!$user->hasRole(Role::RoleSuperAdministrator)) {
            $backups->whereIn('unit_id', $user->employee->getAllUnitID());
        }
        $backups->with(['unit', 'job', 'timesheet', 'assignee', 'backupHistory']);
        $backups->orderBy('created_at', 'desc');

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $backups->get(),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        /**
         * @var Backup $backup
         */
        $backup = Backup::query()->with(['unit', 'job', 'timesheet', 'assignee', 'backupHistory'])->find($id);
        if (!$backup) {
            return response()->json([
                'status' => 'error',
                'message' => 'Backup id not found',
            ], 404);
        }

        if (!$user->hasRole(Role::RoleSuperAdministrator)) {
            if (!in_array($backup->unit_id, $user->employee->getAllUnitID())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit id not found',
                ], 404);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $backup,
        ]);
    }

    public function create(Request $request) {
        /**
         * @var Unit $unitExists
         */
        $unitExists = Unit::query()->where('id', '=', $request->input('unit_id'))->first();
        if (!$unitExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit id not found',
            ], 404);
        }

        /**
         * @var Job $jobExists
         */
        $jobExists = Job::query()->where('id', '=', $request->input('job_id'))->first();
        if (!$jobExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job id not found',
            ], 404);
        }

        /**
         * @var Employee $employeeExists
         */
        $employeeExists = Employee::query()->find($request->input('assignee_id'));
        if (!$employeeExists || (!$employeeExists->hasUnitID($unitExists->relation_id))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee id not found',
            ], 404);
        }

        $backupExists = Backup::leftJoin('backup_histories', 'backups.id', '=', 'backup_histories.backup_id')
            ->where('assignee_id', $request->input('assignee_id'))
            ->where('backup_histories.status', 'assigned')
            ->orWhere('backup_histories.status', 'in_progress')
            ->first();

        if ($backupExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Backup already exists',
            ], 400);
        }

        if ($request->input('shift_type') === Backup::TypeShift) {
            $timesheet = EmployeeTimesheet::find($request->input('timesheet_id'));
            if (!$timesheet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Timesheet id not found',
                ], 404);
            }
        }

        $startDate = date_create($request->input('start_date'));
        $endDate = date_create($request->input('end_date'));
        $diff = date_diff($startDate, $endDate);
        $diffInDays = intval($diff->format("%a"));
        if (($diffInDays + 1) !== $request->input('duration')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Duration is not in start_date and end_date difference',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $backup = Backup::create([
                'unit_id' => $unitExists->id,
                'job_id' => $jobExists->id,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'timesheet_id' => $request->input('shift_type') === Backup::TypeShift ? $request->input('timesheet_id') : null,
                'assignee_id' => $request->input('assignee_id'),
                'shift_type' => $request->input('shift_type'),
                'duration' => $request->input('duration'),
            ]);
            $backup->save();

            $backupHistory = BackupHistory::create([
                'backup_id' => $backup->id,
                'status' => 'assigned',
            ]);
            $backupHistory->save();

            DB::commit();

            $getUser = User::where('employee_id', $request->input('assignee_id'))->first();
            try {
                Notification::send(null,new AssignBackupRequestNotification($getUser->fcm_token));
            } catch (Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 500);
            }

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

    public function approve($id) {
        $idExsists = Backup::where('assignee_id', $id)->first();
        if (!$idExsists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Backup id not found',
            ], 404);
        }

        $backupHistory = BackupHistory::where('backup_id', $idExsists->id)
            ->whereNot('status', 'completed')
            ->first();

        if (!$backupHistory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Backup already approved',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $backupHistory->status = 'completed';
            $backupHistory->save();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success approve backup',
                'data' => $backupHistory,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkIn($request, $id) {
        $empData = Employee::find($id);
        if (!$empData) {
            return response()->json([
                'message' => 'Employee not found!'
            ], 400);
        }

        $backupDetail = Backup::with(['unit', 'unit.workLocations', 'job', 'timesheet', 'assignee', 'backupHistory'])
            ->where('assignee_id', $id)
            ->first();

        $workLocation = $backupDetail->unit->workLocations;

        if (!$backupDetail) {
            return response()->json([
                'message' => 'Backup not found!'
            ], 400);
        }

        // Check if time is in range
        $employeeTimesheetCheckin = Carbon::parse($backupDetail->timesheet->start_time);
        $employeeTimesheetCheckout = Carbon::parse($backupDetail->timesheet->end_time);
        if ($backupDetail->timesheet->end_time < $backupDetail->timesheet->start_time) {
            $employeeTimesheetCheckout = Carbon::parse($backupDetail->timesheet->end_time)->addDay();
        }
        $parseRequestedTime = Carbon::parse($request->real_check_in);
        $requestedTime = Carbon::createFromTimeString($parseRequestedTime);

        $adjustedCheckin = $employeeTimesheetCheckin->copy()->subMinutes(15);
        $adjustedCheckout = $employeeTimesheetCheckout->copy()->subMinutes(15);

        if (!$parseRequestedTime->between($adjustedCheckin, $adjustedCheckout)) {
            return response()->json([
                'message' => 'Check in time must be between ' . $employeeTimesheetCheckin->toTimeString('minutes') . ' and ' . $employeeTimesheetCheckout->toTimeString('minutes')
            ], 400);
        }
        // Check if time is in range

        // Check if employee has checked in today
        $checkInData = EmployeeAttendance::where('employee_id', $id)->orderBy('id', 'desc')->first();

        if (!$request->real_check_in) {
            if ($request->attendance_types == 'normal' && Carbon::parse($checkInData->real_check_in)->format('Y-m-d') == Carbon::parse($parseRequestedTime)->format('Y-m-d')) {
                return response()->json([
                    'message' => 'You have checked in today!'
                ], 400);
            }
        }
        // Check if employee has checked in today

        // Calulate distance
        $attType = "";
        foreach ($workLocation as $location) {
            $distance = calculateDistance($request->lat, $request->long, floatval($location->lat), floatval($location->long));
            if ($distance <= intval($location->radius)){
                $attType = "onsite";
            } else {
                $attType = "offsite";
            }
        }

        DB::beginTransaction();
        try {
            $checkIn = new EmployeeAttendance();
            $checkIn->employee_id = $id;
            $checkIn->real_check_in = $request->real_check_in;
            $checkIn->checkin_type = $attType;
            $checkIn->checkin_lat = $request->lat;
            $checkIn->checkin_long = $request->long;
            $checkIn->is_need_approval = true;
            $checkIn->attendance_types = $request->attendance_types;
            $checkIn->checkin_real_radius = $distance ?? null;
            $checkIn->approved = false;
            $checkIn->is_late = $requestedTime->subMinutes(15)->greaterThan($employeeTimesheetCheckin);
            if ($requestedTime->subMinutes(15)->greaterThan($employeeTimesheetCheckin)) {
                $checkIn->late_duration = $requestedTime->addMinutes(15)->diffInMinutes($employeeTimesheetCheckin->subMinutes(15));
            } else {
                $checkIn->late_duration = 0;
            }

            if (!$checkIn->save()) {
                throw new Exception('Failed save data!');
            }

            $getUser = User::where('employee_id', $id)->first();
            $getUser->is_backup_checkin = true;

            // update backup histories status into in_progress
            $backupHistory = BackupHistory::where('backup_id', $backupDetail->id)->first();
            if($backupHistory->status === 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Backup already completed!'
                ], 400);
            }

            $createAttendaceHistory = new EmployeeAttendanceHistory();
            $createAttendaceHistory->employee_id = intval($id);
            $createAttendaceHistory->employee_attendances_id = $checkIn->id;
            $createAttendaceHistory->status = 'pending';

            $createBackupHistory = new BackupHistory();
            $createBackupHistory->backup_id = $backupDetail->id;
            $createBackupHistory->status = 'in_progress';

            $getUser->save();
            $createAttendaceHistory->save();
            $createBackupHistory->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Success save data!',
                'data' => $checkIn
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
 }
