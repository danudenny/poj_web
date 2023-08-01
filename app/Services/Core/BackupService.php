<?php

namespace App\Services\Core;

use App\Http\Requests\Backup\BackupApprovalRequest;
use App\Http\Requests\Backup\BackupCheckInRequest;
use App\Http\Requests\Backup\BackupCheckOutRequest;
use App\Http\Requests\Backup\CreateBackupRequest;
use App\Models\ApprovalModule;
use App\Models\ApprovalUser;
use App\Models\Backup;
use App\Models\BackupApproval;
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
use App\Models\Partner;
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
            $backups->where('backups.requestor_employee_id', '=', $user->employee_id);
        } else if ($user->isHighestRole(Role::RoleAdmin)) {
            $backups->join('employees AS reqEmployee', 'reqEmployee.id', '=', 'backups.requestor_employee_id');

            $backups->where(function(Builder $builder) use ($user) {
                $lastUnitID = $user->employee->getLastUnitID();
                if ($requestUnitID = $this->getRequestedUnitID()) {
                    $lastUnitID = $requestUnitID;
                }

                $builder->orWhere('backups.unit_id', '=', $lastUnitID)
                    ->orWhere('backups.source_unit_relation_id', '=', $lastUnitID)
                    ->orWhere('backups.requestor_employee_id', '=', $user->employee_id);
            });
        }

        $backups->when($request->filled('status'), function (Builder $builder) use ($request) {
            $builder->where('backups.status', '=', $request->input('status'));
        });
        $backups->when($request->filled('requestor_employee_id'), function (Builder $builder) use ($request) {
            $builder->where('backups.requestor_employee_id', '=', $request->input('requestor_employee_id'));
        });

        $backups->with(['unit:units.relation_id,name', 'job:jobs.odoo_job_id,name', 'requestorEmployee:employees.id,name', 'sourceUnit:units.relation_id,name']);
        $backups->select(['backups.*']);
        $backups->groupBy('backups.id');
        $backups->orderBy('backups.id', 'desc');

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

        $backup->append('is_can_approve');

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

        $query = BackupEmployeeTime::query()->with(['backupTime.backup', 'employee:employees.id,name'])
            ->join('backup_times', 'backup_employee_times.backup_time_id', '=', 'backup_times.id')
            ->join('backups', 'backups.id', '=', 'backup_times.backup_id')
            ->where('backups.status', '!=', BackupApproval::StatusRejected)
            ->orderBy('backup_times.start_time', 'ASC')
            ->select(['backup_employee_times.*']);

        if ($user->isHighestRole(Role::RoleStaff)) {
            $query->where('employee_id', '=', $user->employee_id);
        } else if ($user->isHighestRole(Role::RoleAdmin)) {
            $query->join('employees', 'employees.id', '=', 'backup_employee_times.employee_id');
            $query->where(function(Builder $builder) use ($user) {
                $lastUnitID = $user->employee->getLastUnitID();
                if ($requestUnitID = $this->getRequestedUnitID()) {
                    $lastUnitID = $requestUnitID;
                }

                $builder->where(function(Builder $builder) use ($lastUnitID) {
                    $builder->orWhere('employees.outlet_id', '=', $lastUnitID)
                        ->orWhere('employees.cabang_id', '=', $lastUnitID)
                        ->orWhere('employees.area_id', '=', $lastUnitID)
                        ->orWhere('employees.kanwil_id', '=', $lastUnitID)
                        ->orWhere('employees.corporate_id', '=', $lastUnitID);
                });
            });
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $this->list($query, $request),
        ]);
    }

    public function listApproval(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $query = BackupApproval::query()->with(['backup', 'backup.unit:units.relation_id,name', 'backup.job:jobs.odoo_job_id,name', 'backup.requestorEmployee:employees.id,name', 'backup.sourceUnit:units.relation_id,name'])
            ->where('user_id', '=', $user->id)
            ->orderBy('id', 'DESC');

        if($status = $request->query('status')) {
            $query->where('status', '=', $status);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $this->list($query, $request),
        ]);
    }

    public function detailBackupEmployee(Request $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $backupEmployee = BackupEmployeeTime::query()->with(['backupTime.backup', 'employee:employees.id,name'])
                ->where('id', '=', $id)
                ->first();
            if (!$backupEmployee) {
                return response()->json([
                    'status' => false,
                    'message' => "Employee Backup Not Found",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Succcess fetch data',
                'data' => $backupEmployee,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
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

            if ($unit->lat == null || $unit->long == null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit latitude or longitude is empty',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var Unit $sourceUnit
             */
            $sourceUnit = Unit::query()->where('id', '=', $request->input('requestor_unit_id'))->first();
            if (!$sourceUnit) {
                return response()->json([
                    'status' => false,
                    'message' => "Source unit not found!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $approvalUserIDs = [];
            $requestType = $request->input('request_type');

            if ($requestType == Backup::RequestTypeAssignment) {
                if ($user->isHighestRole(Role::RoleStaff)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You don\'t have access to do assignment',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            } else {
                /**
                 * @var ApprovalUser[] $approvalUsers
                 */
                $approvalUsers = ApprovalUser::query()
                    ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
                    ->join('approval_modules', 'approvals.approval_module_id', '=', 'approval_modules.id')
                    ->where('approval_modules.name', '=', ApprovalModule::ApprovalBackup)
                    ->where('approvals.unit_id', '=', $sourceUnit->id)
                    ->where('approvals.is_active', '=', true)
                    ->orderBy('approval_users.id', 'ASC')
                    ->get(['approval_users.*']);

                foreach ($approvalUsers as $approvalUser) {
                    $approvalUserIDs[] = $approvalUser->user_id;
                }
            }

            $lat = floatval(str_replace(',', '.', $unit->lat));
            $long = floatval(str_replace(',', '.', $unit->long));
            $unitTimeZone = getTimezoneV2($lat, $long);

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
                        ->where(function(Builder $builder) use ($backupDateData) {
                            $builder->orWhere(function(Builder $builder) use ($backupDateData) {
                                $builder->where( 'overtime_dates.start_time', '<=', $backupDateData['start_time'])
                                    ->where('overtime_dates.end_time', '>=', $backupDateData['start_time']);
                            })->orWhere(function(Builder $builder) use ($backupDateData) {
                                $builder->where('overtime_dates.start_time', '<=', $backupDateData['end_time'])
                                    ->where('overtime_dates.end_time', '>=', $backupDateData['end_time']);
                            });
                        })
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
                        ->where('backup_employee_times.employee_id', '=', $employeeID)
                        ->where(function(Builder $builder) use ($backupDateData) {
                            $builder->orWhere(function(Builder $builder) use ($backupDateData) {
                                $builder->where( 'backup_times.start_time', '<=', $backupDateData['start_time'])
                                    ->where('backup_times.end_time', '>=', $backupDateData['start_time']);
                            })->orWhere(function(Builder $builder) use ($backupDateData) {
                                $builder->where('backup_times.start_time', '<=', $backupDateData['end_time'])
                                    ->where('backup_times.end_time', '>=', $backupDateData['end_time']);
                            });
                        })
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
            $backup->location_lat = $lat;
            $backup->location_long = $long;
            $backup->timezone = $unitTimeZone;
            $backup->file_url = $request->input('file_url');
            $backup->request_type = $requestType;
            $backup->source_unit_relation_id = $sourceUnit->relation_id;

            if ($backup->request_type == Backup::RequestTypeAssignment) {
                $backup->status = Backup::StatusApproved;
            }

            $backup->save();

            if ($backup->status == Backup::StatusAssigned && count($approvalUserIDs) > 0) {
                foreach ($approvalUserIDs as $idx => $approvalUserID) {
                    $backupApproval = new BackupApproval();
                    $backupApproval->priority = $idx;
                    $backupApproval->user_id = $approvalUserID;
                    $backupApproval->backup_id = $backup->id;
                    $backupApproval->status = BackupApproval::StatusPending;
                    $backupApproval->save();
                }
            }

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

            if ($endTime->lessThan($startTime)) {
                $endTime->addDays(1);
            }

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

            if (!$user->inRoleLevel([Role::RoleSuperAdministrator, Role::RoleAdmin])) {
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

            /**
             * @var BackupApproval $userApproval
             */
            $userApproval = $backup->backupApprovals()->where('user_id', '=', $user->id)
                ->where('status', '=', BackupApproval::StatusPending)
                ->first();
            if (!$userApproval) {
                return response()->json([
                    'status' => false,
                    'message' => 'You don\'t have access to approve this request',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($userApproval->priority > 0) {
                $beforeApproval = $backup->backupApprovals()->where('priority', '=', $userApproval->priority - 1)
                    ->where('status', '=', BackupApproval::StatusPending)
                    ->exists();
                if ($beforeApproval) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Last approver not doing approval yet!',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            DB::beginTransaction();

            $userApproval->status = $request->input('status');
            $userApproval->notes = $request->input('notes');
            $userApproval->save();

            $backupHistory = new BackupHistory();
            $backupHistory->backup_id = $backup->id;
            $backupHistory->employee_id = $user->employee_id;
            $backupHistory->status = $userApproval->status;
            $backupHistory->notes = $userApproval->notes;
            $backupHistory->save();

            if ($userApproval->status == BackupApproval::StatusRejected) {
                $backup->status = $userApproval->status;

                /**
                 * @var BackupApproval[] $currentBackupApprovals
                 */
                $currentBackupApprovals = $backup->backupApprovals()->where('priority', '>', $userApproval->priority)->get();
                foreach ($currentBackupApprovals as $currentBackupApproval) {
                    $currentBackupApproval->status = BackupApproval::StatusRejected;
                    $currentBackupApproval->save();
                }
            } else {
                /**
                 * @var BackupApproval $lastApproval
                 */
                $lastApproval = $backup->backupApprovals()->orderBy('priority', 'DESC')->first();
                if ($lastApproval->status == BackupApproval::StatusApproved) {
                    $backup->status = BackupApproval::StatusApproved;
                }
            }

            $backup->save();

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
            $employeeTimezone = getTimezoneV2(floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));
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

            $employeeBackup->employee_attendance_id = $checkIn->id;
            $employeeBackup->save();

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
            $employeeTimezone = getTimezoneV2(floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));
            $distance = calculateDistance($backup->location_lat, $backup->location_long,floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));

            $checkOutType = EmployeeAttendance::TypeOnSite;
            if ($distance > $backup->unit->radius) {
                $checkOutType = EmployeeAttendance::TypeOffSite;
            }

            $checkInData = $employeeBackup->employeeAttendance;

            DB::beginTransaction();

            $employeeBackup->check_out_lat = $dataLocation['latitude'];
            $employeeBackup->check_out_long = $dataLocation['longitude'];
            $employeeBackup->check_out_time = Carbon::now();
            $employeeBackup->check_out_timezone = $employeeTimezone;
            $employeeBackup->save();

            if (!is_null($checkInData)) {
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

    public function monthlyEvaluate(Request $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $query = BackupEmployeeTime::query()->with(['backupTime.backup', 'employee:employees.id,name', 'employeeAttendance'])
                ->join('backup_times', 'backup_employee_times.backup_time_id', '=', 'backup_times.id')
                ->join('backups', 'backups.id', '=', 'backup_times.backup_id')
                ->where('backups.status', '!=', BackupApproval::StatusRejected)
                ->orderBy('backup_times.start_time', 'ASC')
                ->where('employee_id', '=', $user->employee_id)
                ->select(['backup_employee_times.*']);

            if ($monthly = $request->query('monthly')) {
                $query->whereRaw("TO_CHAR(backup_times.start_time, 'YYYY-mm') = ?", [$monthly]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Succcess fetch data',
                'data' => [
                    'meta' => [
                        'full_attendance' => (clone $query)->whereNotNull('backup_employee_times.check_in_time')->whereNotNull('backup_employee_times.check_out_time')->count(),
                        'late_check_in' => (clone $query)->whereRaw('backup_employee_times.check_in_time > backup_times.start_time')->count(),
                        'not_check_out' => (clone $query)->whereNull('backup_employee_times.check_out_time')->count(),
                        'early_check_out' => (clone $query)->whereRaw('backup_employee_times.check_out_time < backup_times.end_time')->count(),
                        'not_attendance' => (clone $query)->whereNull('backup_employee_times.check_in_time')->whereNull('backup_employee_times.check_out_time')->count(),
                        'total_schedule' => (clone $query)->count()
                    ],
                    'data' => $this->list($query, $request)
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
 }
