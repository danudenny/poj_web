<?php

namespace App\Services\Core;

use App\Http\Requests\Backup\BackupApprovalRequest;
use App\Http\Requests\Backup\BackupCheckInRequest;
use App\Http\Requests\Backup\BackupCheckOutRequest;
use App\Http\Requests\Backup\CreateBackupRequest;
use App\Models\Backup;
use App\Models\BackupEmployee;
use App\Models\BackupEmployeeTime;
use App\Models\BackupHistory;
use App\Models\BackupTime;
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
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class BackupService
{
    public function index(Request $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        $backups = Backup::query();
        if ($user->inRoleLevel([Role::RoleAdmin])) {
            $backups->whereIn('unit_id', $user->employee->getAllUnitID());
        } else if ($user->inRoleLevel([Role::RoleStaff])) {
            $backups->where('requestor_employee_id', '=', $user->employee_id);
        }

        $backups->with(['unit:units.relation_id,name', 'job:jobs.odoo_job_id,name', 'requestorEmployee:employees.id,name']);
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

    public function create(CreateBackupRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var Unit $unit
             */
            $unit = Unit::query()->where('relation_id', '=', $request->input('unit_relation_id'))->first();
            if (!$unit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit not exist',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var Job $job
             */
            $job = Job::query()->where('id', '=', $request->input('job_id'))->first();
            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job not exist',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $unitTimeZone = getTimezoneV2($unit->lat, $unit->long);

            $employeeIDs = $request->input('employee_ids', []);
            $backupDates = $this->generateBackupDateData($request->input('dates'), $employeeIDs, $unitTimeZone);
            if (count($backupDates) == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no dates',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $backup = new Backup();
            $backup->requestor_employee_id = $user->employee_id;
            $backup->unit_id = $unit->relation_id;
            $backup->job_id = $job->odoo_job_id;
            $backup->start_date = $backupDates[0]['date'];
            $backup->end_date = $backupDates[count($backupDates) - 1]['date'];
            $backup->shift_type = $request->input('shift_type');
            $backup->duration = count($backupDates);
            $backup->status = Backup::StatusAssigned;
            $backup->location_lat = $unit->lat;
            $backup->location_long = $unit->long;
            $backup->save();

            foreach ($backupDates as $backupDate) {
                $backupTime = new BackupTime();
                $backupTime->backup_id = $backup->id;
                $backupTime->backup_date = $backupDate['date'];
                $backupTime->start_time = $backupDate['start_time'];
                $backupTime->end_time = $backupDate['end_time'];
                $backupTime->save();

                foreach ($backupDate['employee_ids'] as $employeeID) {
                    $backupEmployeeTime = new BackupEmployeeTime();
                    $backupEmployeeTime->employee_id = $employeeID;
                    $backupEmployeeTime->backup_time_id = $backupTime->id;
                    $backupEmployeeTime->save();
                }
            }

            foreach ($employeeIDs as $employeeID) {
                $backupEmployee = new BackupEmployee();
                $backupEmployee->backup_id = $backup->id;
                $backupEmployee->employee_id = $employeeID;
                $backupEmployee->save();
            }

            $backupHistory = new BackupHistory();
            $backupHistory->backup_id = $backup->id;
            $backupHistory->employee_id = $user->employee_id;
            $backupHistory->status = $backup->status;
            $backupHistory->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success create backup',
                'data' => [],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array[] $dates
     * @param int[] $employeeIDs
     * @param string $timezone
     * @return array
     */
    private function generateBackupDateData(array $dates, array $employeeIDs, string $timezone): array {
        $results = [];
        $sortedDates = array_keys($dates);
        sort($sortedDates);

        foreach ($sortedDates as $sortedDate) {
            $data = $dates[$sortedDate];

            $startTime = Carbon::createFromFormat('Y-m-d H:i:s', sprintf('%s %s:00', $sortedDate, $data['start_time']), $timezone)->setTimezone('UTC');
            $endTime = Carbon::createFromFormat('Y-m-d H:i:s', sprintf('%s %s:00', $sortedDate, $data['end_time']), $timezone)->setTimezone('UTC');

            $results[] = [
                'date' => $startTime->format('Y-m-d'),
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'employee_ids' => $employeeIDs
            ];
        }

        return $results;
    }

    public function approve(BackupApprovalRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            if (!$user->inRoleLevel([Role::RoleSuperAdministrator, Role::RoleStaff])) {
                return response()->json([
                    'status' => false,
                    'message' => 'You don\'t have access!',
                ], ResponseAlias::HTTP_UNAUTHORIZED);
            }

            /**
             * @var Backup $backup
             */
            $backup = Backup::query()->where('id', '=', $id)
                ->where('status', '=', Backup::StatusAssigned)
                ->first();
            if (!$backup) {
                return response()->json([
                    'status' => false,
                    'message' => 'Backup Not Found',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $backup->status = $request->input('status');
            $backup->save();

            $backupHistory = new BackupHistory();
            $backupHistory->backup_id = $backup->id;
            $backupHistory->employee_id = $user->employee_id;
            $backupHistory->status = $backup->status;
            $backupHistory->notes = $request->input('notes');
            $backupHistory->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Success approve backup',
                'data' => [],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkIn(BackupCheckInRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $dataLocation = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude')
            ];

            /**
             * @var BackupEmployeeTime $employeeBackup
             */
            $employeeBackup = BackupEmployeeTime::query()
                ->join('backup_times', 'backup_employee_times.backup_time_id', '=', 'backup_times.id')
                ->join('backups', 'backup_times.backup_id', '=', 'backups.id')
                ->where('backup_employee_times.id', '=', $id)
                ->where('backups.status', '!=', Backup::StatusRejected)
                ->where('backup_times.end_time', '>', Carbon::now()->format('Y-m-d H:i:s'))
                ->whereNull('backup_employee_times.check_in_time')
                ->where('backup_employee_times.employee_id', '=', $user->employee_id)
                ->orderBy('backup_times.start_time', 'ASC')
                ->select(['backup_employee_times.*'])
                ->first();
            if (!$employeeBackup) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no backup time need to checked in!',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $backup = $employeeBackup->backupTime->backup;
            $employeeTimezone = getTimezoneV2($dataLocation['latitude'], $dataLocation['longitude']);
            $distance = calculateDistance($backup->location_lat, $backup->location_long, $dataLocation['latitude'], $dataLocation['longitude']);

            $checkInType = EmployeeAttendance::TypeOnSite;
            if ($distance > $backup->unit->radius) {
                $checkInType = EmployeeAttendance::TypeOffSite;
            }

            DB::beginTransaction();

            $employeeBackup->check_in_lat = $dataLocation['latitude'];
            $employeeBackup->check_in_long = $dataLocation['longitude'];
            $employeeBackup->check_in_time = Carbon::now();
            $employeeBackup->check_in_timezone = $employeeTimezone;
            $employeeBackup->save();

            $checkIn = new EmployeeAttendance();
            $checkIn->employee_id = $user->employee_id;
            $checkIn->real_check_in = $employeeBackup->check_in_time;
            $checkIn->checkin_type = $checkInType;
            $checkIn->checkin_lat = $employeeBackup->check_in_lat;
            $checkIn->checkin_long = $employeeBackup->check_in_long;
            $checkIn->is_need_approval = $checkInType == EmployeeAttendance::TypeOffSite;
            $checkIn->attendance_types = EmployeeAttendance::AttendanceTypeBackup;
            $checkIn->checkin_real_radius = $distance;
            $checkIn->approved = !($checkInType == EmployeeAttendance::TypeOffSite);
            $checkIn->check_in_tz = $employeeTimezone;
            $checkIn->is_late = false;
            $checkIn->late_duration = 0;
            $checkIn->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success check in',
                'data' => [],
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkOut(BackupCheckOutRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $dataLocation = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude')
            ];

            /**
             * @var BackupEmployeeTime $employeeBackup
             */
            $employeeBackup = BackupEmployeeTime::query()
                ->join('backup_times', 'backup_employee_times.backup_time_id', '=', 'backup_times.id')
                ->join('backups', 'backup_times.backup_id', '=', 'backups.id')
                ->where('backup_employee_times.id', '=', $id)
                ->where('backups.status', '!=', Backup::StatusRejected)
                ->whereNotNull('backup_employee_times.check_in_time')
                ->whereNull('backup_employee_times.check_out_time')
                ->where('backup_employee_times.employee_id', '=', $user->employee_id)
                ->orderBy('backup_times.start_time', 'ASC')
                ->select(['backup_employee_times.*'])
                ->first();
            if (!$employeeBackup) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no backup time need to checked out!',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $backup = $employeeBackup->backupTime->backup;
            $employeeTimezone = getTimezoneV2($dataLocation['latitude'], $dataLocation['longitude']);
            $distance = calculateDistance($backup->location_lat, $backup->location_long, $dataLocation['latitude'], $dataLocation['longitude']);

            $checkOutType = EmployeeAttendance::TypeOnSite;
            if ($distance > $backup->unit->radius) {
                $checkOutType = EmployeeAttendance::TypeOffSite;
            }

            /**
             * @var EmployeeAttendance $checkInData
             */
            $checkInData = $user->employee->attendances()
                ->where('attendance_types', '=', EmployeeAttendance::AttendanceTypeBackup)
                ->orderBy('id', 'DESC')
                ->first();

            DB::beginTransaction();

            $employeeBackup->check_out_lat = $dataLocation['latitude'];
            $employeeBackup->check_out_long = $dataLocation['longitude'];
            $employeeBackup->check_out_time = Carbon::now();
            $employeeBackup->check_out_timezone = $employeeTimezone;
            $employeeBackup->save();

            if ($checkInData) {
                $checkInData->real_check_out = $employeeBackup->check_out_time;
                $checkInData->checkout_lat = $employeeBackup->check_out_lat;
                $checkInData->checkout_long = $employeeBackup->check_out_long;
                $checkInData->checkout_real_radius = $distance;
                $checkInData->checkout_type = $checkOutType;
                $checkInData->check_out_tz = $employeeBackup->check_out_timezone;
                $checkInData->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success check out',
                'data' => [],
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
