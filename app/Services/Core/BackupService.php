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
use App\Models\EmployeeNotification;
use App\Models\OvertimeEmployee;
use App\Models\OvertimeHistory;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\AssignBackupRequestNotification;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class BackupService extends BaseService
{
    public function index(Request $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        $backups = Backup::query();
        if ($user->isHighestRole(Role::RoleStaff)) {
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
        $backup = Backup::query()->with(['unit', 'job', 'backupHistory', 'backupTimes.backupEmployees', 'backupTimes.backupEmployees.employee:employees.id,name', 'backupEmployees', 'requestorEmployee:employees.id,name'])->find($id);
        if (!$backup) {
            return response()->json([
                'status' => 'error',
                'message' => 'Backup id not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $backup,
        ]);
    }

    public function listEmployeeBackup(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $query = BackupEmployeeTime::query()->with(['backupTime.backup'])
            ->join('backup_times', 'backup_employee_times.backup_time_id', '=', 'backup_times.id')
            ->where('backup_times.start_time', '>=', Carbon::now()->addDays(-1)->format('Y-m-d H:i:s'))
            ->orderBy('backup_times.start_time', 'ASC')
            ->select(['backup_employee_times.*']);

        if ($user->isHighestRole(Role::RoleStaff)) {
            $query->where('employee_id', '=', $user->employee_id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $this->list($query, $request),
        ]);
    }

    public function create(CreateBackupRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();
            $lastUnit = $user->employee->last_unit;

            /**
             * @var Unit $unit
             */
            $unit = Unit::query()->where('id', '=', $lastUnit->id)->first();
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

            $unitTimeZone = getTimezone($unit->lat, $unit->long);

            $employeeIDs = $request->input('employee_ids', []);
            $backupDates = $this->generateBackupDateData($request->input('dates'), $employeeIDs, $unitTimeZone);
            if (count($backupDates) == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no dates',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            foreach ($backupDates as $backupDateData) {
                foreach ($employeeIDs as $employeeID) {
                    $isExistOvertime = OvertimeEmployee::query()
                        ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
                        ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
                        ->where('overtime_employees.employee_id', '=', $employeeID)
                        ->where('overtimes.last_status', '!=', OvertimeHistory::TypeRejected)
                        ->where('overtime_dates.date', '=', $backupDateData['date'])
                        ->exists();
                    if ($isExistOvertime) {
                        /**
                         * @var Employee $employee
                         */
                        $employee = Employee::query()->where('id', '=', $employeeID)->first();

                        return response()->json([
                            'status' => false,
                            'message' => sprintf("%s has active overtime in %s", $employee->name, $backupDateData['date']),
                        ], ResponseAlias::HTTP_BAD_REQUEST);
                    }

                    $isExistBackup = BackupEmployeeTime::query()
                        ->join('backup_times', 'backup_employee_times.backup_time_id', '=', 'backup_times.id')
                        ->join('backups', 'backups.id', '=', 'backup_times.backup_id')
                        ->where('status', '!=', Backup::StatusRejected)
                        ->where('backup_times.backup_date', '=', $backupDateData['date'])
                        ->where('backup_employee_times.employee_id', '=', $employeeID)
                        ->exists();
                    if ($isExistBackup) {
                        /**
                         * @var Employee $employee
                         */
                        $employee = Employee::query()->where('id', '=', $employeeID)->first();

                        return response()->json([
                            'status' => false,
                            'message' => sprintf("%s has active backup in %s", $employee->name, $backupDateData['date']),
                        ], ResponseAlias::HTTP_BAD_REQUEST);
                    }
                }
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
            $backup->timezone = $unitTimeZone;
            $backup->file_url = $request->input('file_url');
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

                $this->getNotificationService()->createNotification(
                    $employeeID,
                    'Pelaksanaan Backup',
                    count($backupDates) == 0 ? $backupDate[0]['date'] : sprintf("%s - %s", $backupDates[0]['date'], $backupDates[count($backupDates) - 1]['date']),
                    'Backup Pegawai',
                    EmployeeNotification::ReferenceBackup,
                    $backup->id
                )->withSendPushNotification()->send();
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
                ->where('id', '=', $id)
                ->whereNull('check_in_time')
                ->first();
            if (!$employeeBackup) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no backup time need to checked in!',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $backup = $employeeBackup->backupTime->backup;
            $employeeTimezone = getTimezone(floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));
            $distance = calculateDistance($backup->location_lat, $backup->location_long, floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));

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

            $this->getNotificationService()->createNotification(
                $user->employee_id,
                'Check In Backup',
                'Check In Backup Telah Berhasil'
            )->withSendPushNotification()->silent()->send();

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
        } catch (GuzzleException $e) {
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
                ->where('id', '=', $id)
                ->whereNull('check_out_time')
                ->whereNotNull('check_in_time')
                ->first();
            if (!$employeeBackup) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no backup time need to checked out!',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $backup = $employeeBackup->backupTime->backup;
            $employeeTimezone = getTimezone(floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));
            $distance = calculateDistance($backup->location_lat, $backup->location_long,floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));

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

            $this->getNotificationService()->createNotification(
                $user->employee_id,
                'Check In Backup',
                'Check In Backup Telah Berhasil'
            )->withSendPushNotification()->silent()->send();

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

    public function getActiveEmployeeDate(Request $request, int $id) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $backupEmployeeTime = BackupEmployeeTime::query()->with(['backupTime.backup'])
            ->join('backup_times', 'backup_employee_times.backup_time_id', '=', 'backup_times.id')
            ->where('backup_times.start_time', '>=', Carbon::now()->addDays(-1)->format('Y-m-d H:i:s'))
            ->where('backup_times.backup_id', '=', $id)
            ->where('backup_employee_times.employee_id', '=', $user->employee_id)
            ->where(function(Builder $builder) {
                $builder->orWhereNull('backup_employee_times.check_in_time')
                    ->orWhereNull('backup_employee_times.check_out_time');
            })
            ->orderBy('backup_times.start_time', 'ASC')
            ->select(['backup_employee_times.*'])
            ->first();

        if (!$backupEmployeeTime) {
            return response()->json([
                'status' => false,
                'message' => 'There is no active backup!',
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $backupEmployeeTime
        ]);
    }
 }
