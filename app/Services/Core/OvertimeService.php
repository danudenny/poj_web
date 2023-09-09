<?php

namespace App\Services\Core;

use App\Http\Requests\Overtime\OvertimeCheckInRequest;
use App\Http\Requests\Overtime\CreateOvertimeRequest;
use App\Http\Requests\Overtime\OvertimeApprovalRequest;
use App\Http\Requests\Overtime\OvertimeCheckOutRequest;
use App\Models\ApprovalModule;
use App\Models\ApprovalUser;
use App\Models\AttendanceApproval;
use App\Models\Backup;
use App\Models\BackupEmployeeTime;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Job;
use App\Models\EmployeeNotification;
use App\Models\Overtime;
use App\Models\OvertimeApproval;
use App\Models\OvertimeDate;
use App\Models\OvertimeEmployee;
use App\Models\OvertimeHistory;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class OvertimeService extends ScheduleService
{
    private ApprovalService $approvalService;

    public function __construct()
    {
        $this->approvalService = new ApprovalService();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $unitRelationID = $request->get('unit_relation_id');

        $overtimes = Overtime::query()->with(['requestorEmployee:employees.id,name', 'unit:units.relation_id,name']);
        $overtimes->join('overtime_dates', 'overtime_dates.overtime_id', '=', 'overtimes.id');
        $overtimes->join('overtime_employees', 'overtime_employees.overtime_date_id', '=', 'overtime_dates.id');
        $overtimes->join('employees', 'employees.id', '=', 'overtime_employees.employee_id');
        $overtimes->join('employees AS reqEmployee', 'reqEmployee.id', '=', 'overtimes.requestor_employee_id');

        if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

        } else if ($this->isRequestedRoleLevel(Role::RoleAdminUnit)) {
            if (!$unitRelationID) {
                $defaultUnitRelationID = $user->employee->unit_id;

                if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                    $defaultUnitRelationID = $requestUnitRelationID;
                }

                $unitRelationID = $defaultUnitRelationID;
            }

        } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
            $overtimes->leftJoin('user_operating_units', 'user_operating_units.unit_relation_id', '=', 'employees.default_operating_unit_id');
            $overtimes->where(function (Builder $builder) use ($user) {
                $builder->orWhere('user_operating_units.user_id', '=', $user->id);
            });
        } else {
            $subQuery = "(
                            WITH RECURSIVE job_data AS (
                                SELECT * FROM unit_has_jobs
                                WHERE unit_relation_id = '{$user->employee->unit_id}' AND odoo_job_id = {$user->employee->job_id}
                                UNION ALL
                                SELECT uj.* FROM unit_has_jobs uj
                                INNER JOIN job_data jd ON jd.id = uj.parent_unit_job_id
                            )
                            SELECT * FROM job_data
                        ) relatedJob";
            $overtimes->join(DB::raw($subQuery), function (JoinClause $joinClause) {
                $joinClause->on(DB::raw("relatedJob.odoo_job_id"), '=', 'employees.job_id')
                    ->where(DB::raw("relatedJob.unit_relation_id"), '=', DB::raw('"employees"."unit_id"'));
            });

            $overtimes->where(function (Builder $builder) use ($user) {
                $builder->orWhere(function(Builder $builder) use ($user) {
                    $builder->where(DB::raw('"employees"."job_id"'), '=', $user->employee->job_id)
                        ->where(DB::raw('"employees"."unit_id"'), '=', $user->employee->unit_id)
                        ->where(DB::raw('"employees"."id"'), '=', $user->employee_id);
                })->orWhere(function (Builder $builder) use ($user) {
                    $builder->orWhere(DB::raw('"employees"."job_id"'), '!=', $user->employee->job_id)
                        ->orWhere(DB::raw('"employees"."unit_id"'), '!=', $user->employee->unit_id);
                });
            });
        }

        if ($unitRelationID) {
            $overtimes->where(function(Builder $builder) use ($unitRelationID) {
                $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                        ->orWhere('employees.cabang_id', '=', $unitRelationID)
                        ->orWhere('employees.area_id', '=', $unitRelationID)
                        ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                        ->orWhere('employees.corporate_id', '=', $unitRelationID);
                })->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('reqEmployee.outlet_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.cabang_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.area_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.kanwil_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.corporate_id', '=', $unitRelationID);
                });
            });
        }

        $overtimes->when($request->filled('unit_name'), function(Builder $builder) use ($request) {
            $builder->join('units', 'units.relation_id', '=', 'overtimes.unit_relation_id')
                ->whereRaw('LOWER(units.name) LIKE ?', ['%'.strtolower($request->query('unit_name')).'%']);
        });
        $overtimes->when($request->filled('last_status'), function(Builder $builder) use ($request) {
            $builder->whereRaw('LOWER(overtimes.last_status) LIKE ?', ['%'.strtolower($request->query('last_status')).'%']);
        });

        if ($status = $request->get('status')) {
            $overtimes->whereIn('overtimes.last_status', explode(",", $status));
        }

        if ($startDate = $request->get('start_date')) {
            $overtimes->where('overtime_dates.date', ">=", $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $overtimes->where('overtime_dates.date', "<=", $endDate);
        }

        if ($requestorName = $request->get('requestor_name')) {
            $overtimes->where("reqEmployee.name", 'ILIKE', "%$requestorName%");
        }
        $overtimes->when($request->filled('requestor_employee_id'), function(Builder $builder) use ($request) {
            $builder->where('overtimes.requestor_employee_id', '=', $request->input('requestor_employee_id'));
        });
        if ($requestType = $request->query('request_type')) {
            $overtimes->where('overtimes.request_type', '=', $requestType);
        }

        $overtimes->select(['overtimes.*']);
        $overtimes->groupBy(['overtimes.id']);
        $overtimes->orderBy('overtimes.id', 'DESC');

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($overtimes, $request)
        ], Response::HTTP_OK);
    }

    public function view(Request $request, int $id) {
        /**
         * @var User $user
         */
        $user = $request->user();

        /**
         * @var Overtime $overtime
         */
        $overtime = Overtime::query()
            ->with([
                'requestorEmployee', 'unit', 'overtimeApprovals.employee',
                'overtimeHistories', 'overtimeHistories.employee:employees.id,name',
                'overtimeDates', 'overtimeDates.overtimeEmployees', 'overtimeDates.overtimeEmployees.employee:employees.id,name'
            ])->where('id', '=', $id)
            ->first();
        if (!$overtime) {
            return response()->json([
                'status' => false,
                'message' => "overtime Not Found"
            ], Response::HTTP_BAD_REQUEST);
        }

        $overtime->append('is_can_approve');

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $overtime
        ], Response::HTTP_OK);
    }

    public function listEmployeeOvertime(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $unitRelationID = $request->get('unit_relation_id');
        $query = OvertimeEmployee::query()
            ->with(['employee:employees.id,name', 'overtimeDate.overtime'])
            ->join('employees', 'employees.id', '=', 'overtime_employees.employee_id')
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
            ->where('overtimes.last_status', '!=', OvertimeHistory::TypeRejected);

        if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

        } else if ($this->isRequestedRoleLevel(Role::RoleAdminUnit)) {
            if (!$unitRelationID) {
                $defaultUnitRelationID = $user->employee->unit_id;

                if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                    $defaultUnitRelationID = $requestUnitRelationID;
                }

                $unitRelationID = $defaultUnitRelationID;
            }

        } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
            $query->leftJoin('user_operating_units', 'user_operating_units.unit_relation_id', '=', 'employees.default_operating_unit_id');
            $query->where(function (Builder $builder) use ($user) {
                $builder->orWhere('user_operating_units.user_id', '=', $user->id);
            });
        } else {
            $subQuery = "(
                            WITH RECURSIVE job_data AS (
                                SELECT * FROM unit_has_jobs
                                WHERE unit_relation_id = '{$user->employee->unit_id}' AND odoo_job_id = {$user->employee->job_id}
                                UNION ALL
                                SELECT uj.* FROM unit_has_jobs uj
                                INNER JOIN job_data jd ON jd.id = uj.parent_unit_job_id
                            )
                            SELECT * FROM job_data
                        ) relatedJob";
            $query->join(DB::raw($subQuery), function (JoinClause $joinClause) {
                $joinClause->on(DB::raw("relatedJob.odoo_job_id"), '=', 'employees.job_id')
                    ->where(DB::raw("relatedJob.unit_relation_id"), '=', DB::raw('"employees"."unit_id"'));
            });

            $query->where(function (Builder $builder) use ($user) {
                $builder->orWhere(function(Builder $builder) use ($user) {
                    $builder->where(DB::raw('"employees"."job_id"'), '=', $user->employee->job_id)
                        ->where(DB::raw('"employees"."unit_id"'), '=', $user->employee->unit_id)
                        ->where(DB::raw('"employees"."id"'), '=', $user->employee_id);
                })->orWhere(function (Builder $builder) use ($user) {
                    $builder->orWhere(DB::raw('"employees"."job_id"'), '!=', $user->employee->job_id)
                        ->orWhere(DB::raw('"employees"."unit_id"'), '!=', $user->employee->unit_id);
                });
            });
        }

        if ($unitRelationID) {
            $query->where(function(Builder $builder) use ($unitRelationID) {
                $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                        ->orWhere('employees.cabang_id', '=', $unitRelationID)
                        ->orWhere('employees.area_id', '=', $unitRelationID)
                        ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                        ->orWhere('employees.corporate_id', '=', $unitRelationID);
                });
            });
        }

        $query->select(['overtime_employees.*']);
        $query->groupBy('overtime_employees.id', 'overtimes.start_date');
        $query->orderBy('overtimes.start_date', 'DESC');

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($query, $request)
        ], Response::HTTP_OK);
    }

    public function listApprovalOvertime(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $unitRelationID = $request->get('unit_relation_id');

        $query = OvertimeApproval::query()->with(['overtime', 'employee', 'overtime.requestorEmployee:employees.id,name', 'overtime.unit:units.relation_id,name'])
            ->join('overtimes', 'overtimes.id', '=', 'overtime_approvals.overtime_id');
        $query->join('overtime_dates', 'overtime_dates.overtime_id', '=', 'overtimes.id');
        $query->join('overtime_employees', 'overtime_employees.overtime_date_id', '=', 'overtime_dates.id');
        $query->join('employees', 'employees.id', '=', 'overtime_employees.employee_id');
        $query->join('employees AS reqEmployee', 'reqEmployee.id', '=', 'overtimes.requestor_employee_id');;
        $query->join('employees AS approvalEmployee', 'approvalEmployee.id', '=', 'overtime_approvals.employee_id');;

        if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

        } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
            if (!$unitRelationID) {
                $defaultUnitRelationID = $user->employee->unit_id;

                if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                    $defaultUnitRelationID = $requestUnitRelationID;
                }

                $unitRelationID = $defaultUnitRelationID;
            }
        } else {
            $query->where('overtime_approvals.employee_id', '=', $user->employee_id);
        }

        if ($unitRelationID) {
            $query->where(function(Builder $builder) use ($unitRelationID) {
                $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                        ->orWhere('employees.cabang_id', '=', $unitRelationID)
                        ->orWhere('employees.area_id', '=', $unitRelationID)
                        ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                        ->orWhere('employees.corporate_id', '=', $unitRelationID);
                })->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('reqEmployee.outlet_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.cabang_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.area_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.kanwil_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.corporate_id', '=', $unitRelationID);
                })->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('approvalEmployee.outlet_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.cabang_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.area_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.kanwil_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.corporate_id', '=', $unitRelationID);
                });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('overtime_approvals.status', '=', $status);
        }

        $query->select(['overtime_approvals.*'])
            ->groupBy('overtime_approvals.id')
            ->orderBy('overtime_approvals.id', 'DESC');

        return response()->json([
            'status' => 'success',
            'message' => 'Succcess fetch data',
            'data' => $this->list($query, $request),
        ]);
    }

    public function detailEmployeeOvertime(Request $request, int $id) {
        try {
            $employeeOvertime = OvertimeEmployee::query()
                ->with(['employee:employees.id,name', 'overtimeDate.overtime'])
                ->where('id', '=', $id)
                ->first();

            if (!$employeeOvertime) {
                return response()->json([
                    'status' => false,
                    'message' => "Employee Overtime Not Found",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Succcess fetch data',
                'data' => $employeeOvertime,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param CreateOvertimeRequest $request
     * @return JsonResponse
     */
    public function create(CreateOvertimeRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();
            $requestorEmployee = $user->employee;
            $lastUnit = $user->employee->last_unit;

            /**
             * @var Unit $unit
             */
            $unit = Unit::query()->where('relation_id', '=', $request->input('unit_relation_id'))->first();
            if (!$unit) {
                return response()->json([
                    'status' => false,
                    'message' => "Unit Not Found"
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
                    'message' => 'Unit don\'t have latitude and longitude',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             *  NOTES:
             *
             *  For start_datetime and end_datetime need to unified to be UTC, this changes be more useful for some reasons,
             *  especially for some Users has different timezones. With unified timezone, it will be easier to make sorting
             *  datetime, and also to matching with User current location timezone.
             */

            $lat = floatval(str_replace(',', '.', $unit->lat));
            $long = floatval(str_replace(',', '.', $unit->long));
            $unitTimeZone = getTimezoneV2($lat, $long);

            $employeeIDs = $request->input('employee_ids', []);
            if(count($employeeIDs) > 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Maximum selected employee is one'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }
            $overtimeDates = $this->generateOvertimeDateData($request->input('dates'), $employeeIDs, $unitTimeZone);
            if (count($overtimeDates) == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no dates',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $requestType = $request->input('request_type');

            if ($requestType == Overtime::RequestTypeAssignment) {
                if ($this->isRequestedRoleLevel(Role::RoleStaff)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You don\'t have access to do assignment',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            foreach ($overtimeDates as $overtimeDateData) {
                foreach ($employeeIDs as $employeeID) {
                    $isExist = $this->isEmployeeActiveScheduleExist([$employeeID], $overtimeDateData['start_time'], $overtimeDateData['end_time']);
                    if ($isExist) {
                        /**
                         * @var Employee $employee
                         */
                        $employee = Employee::query()->where('id', '=', $employeeID)->first();
                        return response()->json([
                            'status' => false,
                            'message' => sprintf("%s has active schedule at %s", $employee->name, $overtimeDateData['date']),
                        ], ResponseAlias::HTTP_BAD_REQUEST);
                    }
                }
            }

            DB::beginTransaction();

            $overtime = new Overtime();
            $overtime->requestor_employee_id = $user->employee_id;
            $overtime->unit_relation_id = $unit->relation_id;
            $overtime->job_id = $job->id;
            $overtime->start_date = $overtimeDates[0]['date'];
            $overtime->end_date = $overtimeDates[count($overtimeDates) - 1]['date'];
            $overtime->last_status = OvertimeHistory::TypePending;
            $overtime->last_status_at = Carbon::now();
            $overtime->location_lat = $lat;
            $overtime->location_long = $long;
            $overtime->timezone = $unitTimeZone;
            $overtime->notes = $request->input('notes');
            $overtime->image_url = $request->input('image_url');
            $overtime->request_type = $requestType;

            $overtime->save();

            foreach ($overtimeDates as $overtimeDateData) {
                $overtimeDate = new OvertimeDate();
                $overtimeDate->overtime_id = $overtime->id;
                $overtimeDate->date = $overtimeDateData['date'];
                $overtimeDate->start_time = $overtimeDateData['start_time'];
                $overtimeDate->end_time = $overtimeDateData['end_time'];
                $overtimeDate->total_overtime = $overtimeDateData['diff_times'];
                $overtimeDate->save();

                foreach ($overtimeDateData['employee_ids'] as $employeeID) {
                    $backupEmployeeTime = new OvertimeEmployee();
                    $backupEmployeeTime->employee_id = $employeeID;
                    $backupEmployeeTime->overtime_date_id = $overtimeDate->id;
                    $backupEmployeeTime->save();
                }
            }

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtime->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = $overtime->last_status;
            $overtimeHistory->save();

            foreach ($employeeIDs as $employeeID) {
                $this->getNotificationService()->createNotification(
                    $employeeID,
                    'Pelaksanaan Lembur',
                    count($overtimeDates) == 0 ? $overtimeDates[0]['date'] : sprintf("%s - %s", $overtimeDates[0]['date'], $overtimeDates[count($overtimeDates) - 1]['date']),
                    'Lembur Pegawai',
                    EmployeeNotification::ReferenceOvertime,
                    $overtime->id
                )->withSendPushNotification()->send();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array[] $dates
     * @param int[] $employeeIDs
     * @param string $timezone
     * @return array
     */
    private function generateOvertimeDateData(array $dates, array $employeeIDs, string $timezone): array {
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

            $diff = $startTime->diff($endTime);
            $parsedDiff = sprintf("%02d:%02d:%02d", $diff->h, $diff->i, $diff->s);

            $results[] = [
                'date' => $startTime->format('Y-m-d'),
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'diff_times' => $parsedDiff,
                'employee_ids' => $employeeIDs
            ];
        }

        return $results;
    }

    /**
     * @param OvertimeApprovalRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function approval(OvertimeApprovalRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var Overtime $overtime
             */
            $overtime = Overtime::query()->where('overtimes.id', '=', $id)
                ->where('last_status', '=', OvertimeHistory::TypePending)
                ->first();
            if (!$overtime) {
                return response()->json([
                    'status' => false,
                    'message' => "overtime Not Found"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var OvertimeApproval $userApproval
             */
            $userApproval = $overtime->overtimeApprovals()
                ->where('employee_id', '=', $user->employee_id)
                ->where('status', '=', OvertimeApproval::StatusPending)
                ->first();
            if(!$userApproval) {
                return response()->json([
                    'status' => false,
                    'message' => 'You don\'t have access'
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            if($userApproval->priority > 0) {
                $lastApproval = $overtime->overtimeApprovals()
                    ->where('priority', '<', $userApproval->priority)
                    ->where('status', '=', OvertimeApproval::StatusPending)
                    ->exists();
                if ($lastApproval) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Last approver not doing approval yet!'
                    ], ResponseAlias::HTTP_FORBIDDEN);
                }
            }

            $totalOvertimes = $request->input('dates', []);

            DB::beginTransaction();

            $userApproval->status = $request->input('status');
            $userApproval->notes = $request->input('notes');
            $userApproval->save();

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtime->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = $userApproval->status;
            $overtimeHistory->notes = $userApproval->notes;
            $overtimeHistory->save();

            if ($userApproval->status == OvertimeApproval::StatusRejected) {
                $overtime->last_status = OvertimeApproval::StatusRejected;

                /**
                 * @var OvertimeApproval[] $currentOvertimeApprovals
                 */
                $currentOvertimeApprovals = $overtime->overtimeApprovals()->where('priority', '>', $userApproval->priority)->get();
                foreach ($currentOvertimeApprovals as $currentOvertimeApproval) {
                    $currentOvertimeApproval->status = OvertimeApproval::StatusRejected;
                    $currentOvertimeApproval->save();
                }
            } else {
                $lastApproval = $overtime->overtimeApprovals()
                    ->where('priority', '>', $userApproval->priority)
                    ->where('status', '=', OvertimeApproval::StatusPending)
                    ->first();
                if (!$lastApproval) {
                    $overtime->last_status = OvertimeApproval::StatusApproved;
                }
            }

            foreach ($totalOvertimes as $key => $totalOvertime) {
                /**
                 * @var OvertimeDate $overtimeDate
                 */
                $overtimeDate = OvertimeDate::query()
                    ->where('overtime_id', '=', $overtime->id)
                    ->where('date', '=', $key)
                    ->first();
                if ($overtimeDate) {
                    $overtimeDate->total_overtime = $totalOvertime;
                    $overtimeDate->save();
                }
            }

            $overtime->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkIn(OvertimeCheckInRequest $request, int $id) {
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
             * @var OvertimeEmployee $employeeOvertime
             */
            $employeeOvertime = OvertimeEmployee::query()
                ->where('id', '=', $id)
                ->where('employee_id', '=', $user->employee_id)
                ->whereNull('check_in_time')
                ->first();
            if (!$employeeOvertime) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have any overtime need to check-in"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $employeeTimezone = getTimezoneV2(floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));

            $workLocation = $user->employee->getLastUnit();
            $overtimeRequest = $employeeOvertime->overtimeDate->overtime;
            $distance = calculateDistance($overtimeRequest->location_lat, $overtimeRequest->location_long, floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));

            $isNeedApproval = false;
            $checkInType = EmployeeAttendance::TypeOnSite;
            if ($distance > $workLocation->radius) {
                $checkInType = EmployeeAttendance::TypeOffSite;
                $isNeedApproval = true;
            }

            $currentTime = Carbon::now();
            $checkInTime = Carbon::parse($employeeOvertime->overtimeDate->start_time);
            $minimumCheckInTime = Carbon::parse($employeeOvertime->overtimeDate->start_time)->addMinutes(-$overtimeRequest->unit->early_buffer);
            $maximumCheckInTime = Carbon::parse($employeeOvertime->overtimeDate->start_time)->addMinutes($overtimeRequest->unit->late_buffer);

            $lateDuration = 0;
            $earlyDuration = 0;
            $attendanceStatus = "On Time";
            if ($currentTime->lessThan($minimumCheckInTime)) {
                return response()->json([
                    'status' => false,
                    'message' => sprintf("Minimum check in at %s", $minimumCheckInTime->setTimezone($employeeTimezone)->format('H:i:s'))
                ], ResponseAlias::HTTP_BAD_REQUEST);
            } else if ($currentTime->greaterThanOrEqualTo($minimumCheckInTime) && $currentTime->lessThan($checkInTime)) {
                $attendanceStatus = "Early Check In";
                $earlyDuration = $checkInTime->diffInMinutes($currentTime);
            } else if ($currentTime->greaterThan($maximumCheckInTime)) {
                $attendanceStatus = "Late";
                $lateDuration = $currentTime->diffInMinutes($maximumCheckInTime);
            }

            $approvalEmployeeIDs = [];
            $approvalType = null;
            if ($isNeedApproval && $checkInType == EmployeeAttendance::TypeOffSite) {
                $approvalType = AttendanceApproval::TypeOffsite;
                $approvalUsers = $this->approvalService->getApprovalUser($user->employee, ApprovalModule::ApprovalOffsiteAttendance);
                foreach ($approvalUsers as $approvalUser) {
                    $approvalEmployeeIDs[] = $approvalUser->employee_id;
                }

                if (count($approvalEmployeeIDs) == 0) {
                    $isNeedApproval = false;
                }
            }

            $notes = $request->input('notes');
            if ($approvalType === AttendanceApproval::TypeOffsite && !$notes) {
                return response()->json([
                    'status' => false,
                    'message' => 'Offsite check in, please input notes'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $employeeOvertime->check_in_lat = $dataLocation['latitude'];
            $employeeOvertime->check_in_long = $dataLocation['longitude'];
            $employeeOvertime->check_in_time = $currentTime;
            $employeeOvertime->check_in_timezone = $employeeTimezone;
            $employeeOvertime->save();

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtimeRequest->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = OvertimeHistory::TypeCheckIn;
            $overtimeHistory->save();

            $checkIn = new EmployeeAttendance();
            $checkIn->employee_id = $user->employee_id;
            $checkIn->real_check_in = $employeeOvertime->check_in_time;
            $checkIn->checkin_type = $checkInType;
            $checkIn->checkin_lat = $employeeOvertime->check_in_lat;
            $checkIn->checkin_long = $employeeOvertime->check_in_long;
            $checkIn->is_need_approval = $isNeedApproval;
            $checkIn->attendance_types = EmployeeAttendance::AttendanceTypeOvertime;
            $checkIn->checkin_real_radius = $distance;
            $checkIn->approved = !$isNeedApproval;
            $checkIn->check_in_tz = $employeeTimezone;
            $checkIn->is_late = $lateDuration > 0;
            $checkIn->late_duration = $lateDuration;
            $checkIn->early_duration = $earlyDuration;
            $checkIn->check_in_image_url = $request->input('image_url');
            $checkIn->check_in_notes = $notes;
            $checkIn->save();

            $employeeOvertime->employee_attendance_id = $checkIn->id;
            $employeeOvertime->save();

            foreach ($approvalEmployeeIDs as $idx => $approvalEmployeeID) {
                $attendanceApproval = new AttendanceApproval();
                $attendanceApproval->priority = $idx;
                $attendanceApproval->approval_type = $approvalType;
                $attendanceApproval->employee_attendance_id = $checkIn->id;
                $attendanceApproval->employee_id = $approvalEmployeeID;
                $attendanceApproval->status = AttendanceApproval::StatusPending;
                $attendanceApproval->save();
            }

            DB::commit();

            $this->getNotificationService()->createNotification(
                $user->employee_id,
                'Check In Lembur',
                'Check In Lembur Telah Berhasil'
            )->withSendPushNotification()->silent()->send();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkOut(OvertimeCheckOutRequest $request, int $id): JsonResponse
    {
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
             * @var OvertimeEmployee $employeeOvertime
             */
            $employeeOvertime = OvertimeEmployee::query()
                ->where('id', '=', $id)
                ->where('employee_id', '=', $user->employee_id)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->first();
            if (!$employeeOvertime) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have any overtime need to check-out"
                ], Response::HTTP_BAD_REQUEST);
            }

            $employeeTimezone = getTimezoneV2(floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));
            $workLocation = $employeeOvertime->overtimeDate->overtime->unit;
            $overtimeRequest = $employeeOvertime->overtimeDate->overtime;
            $distance = calculateDistance($overtimeRequest->location_lat, $overtimeRequest->location_long, floatval($dataLocation['latitude']), floatval($dataLocation['longitude']));

            $currentTime = Carbon::now();
            $checkOutTime = Carbon::parse($employeeOvertime->overtimeDate->end_time, 'UTC');

            $earlyDuration = 0;
            if ($currentTime->lessThan($checkOutTime)) {
                $earlyDuration = $checkOutTime->diffInMinutes($currentTime);
            }

            $checkOutType = EmployeeAttendance::TypeOnSite;
            if ($distance > $workLocation->radius) {
                $checkOutType = EmployeeAttendance::TypeOffSite;
            }

            $checkInData = $employeeOvertime->employeeAttendance;

            DB::beginTransaction();

            $employeeOvertime->check_out_lat = $dataLocation['latitude'];
            $employeeOvertime->check_out_long = $dataLocation['longitude'];
            $employeeOvertime->check_out_time = $currentTime;
            $employeeOvertime->check_out_timezone = $employeeTimezone;
            $employeeOvertime->save();

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtimeRequest->id;
            $overtimeHistory->employee_id = $user->employee_id;
            $overtimeHistory->history_type = OvertimeHistory::TypeCheckOut;
            $overtimeHistory->save();

            if (!is_null($checkInData)) {
                $checkInData->real_check_out = $employeeOvertime->check_out_time;
                $checkInData->checkout_lat = $employeeOvertime->check_out_lat;
                $checkInData->checkout_long = $employeeOvertime->check_out_long;
                $checkInData->checkout_real_radius = $distance;
                $checkInData->checkout_type = $checkOutType;
                $checkInData->check_out_tz = $employeeOvertime->check_out_timezone;
                $checkInData->early_check_out = $earlyDuration;
                $checkInData->save();
            }

            $this->assignApproval($employeeOvertime->overtimeDate->overtime);

            DB::commit();

            $this->getNotificationService()->createNotification(
                $user->employee_id,
                'Check Out Lembur',
                'Check Out Lembur Telah Berhasil'
            )->withSendPushNotification()->silent()->send();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function assignApproval(Overtime $overtime) {
        $isPendingCheckExist = OvertimeEmployee::query()
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->where('overtime_dates.overtime_id', '=', $overtime->id)
            ->where(function(Builder $builder) {
                $builder->orWhereNull('overtime_employees.check_in_time')
                    ->orWhereNull('overtime_employees.check_out_time');
            })->exists();
        if ($isPendingCheckExist) {
            return;
        }

        $overtimeDates = $overtime->overtimeDates;
        if (count($overtimeDates) == 0) {
            return;
        }

        $overtimeEmployee = $overtimeDates[0]->overtimeEmployees;
        if (count($overtimeEmployee) == 0) {
            return;
        }

        $employee = $overtimeEmployee[0]->employee;
        $approvalUsers = $this->approvalService->getApprovalUser($employee, ApprovalModule::ApprovalOvertime);

        $approvalUserIDs = [];
        foreach ($approvalUsers as $approvalUser) {
            $approvalUserIDs[] = $approvalUser->employee_id;
        }

        foreach ($approvalUserIDs as $idx => $approvalUserID) {
            $overtimeApproval = new OvertimeApproval();
            $overtimeApproval->priority = $idx;
            $overtimeApproval->employee_id = $approvalUserID;
            $overtimeApproval->overtime_id = $overtime->id;
            $overtimeApproval->status = OvertimeApproval::StatusPending;
            $overtimeApproval->save();
        }

        if (count($approvalUserIDs) == 0) {
            $overtime->last_status = OvertimeHistory::TypeApproved;
            $overtime->last_status_at = Carbon::now();
            $overtime->save();
        }
    }

    private function refreshFinishedStatus(Overtime  $overtime, int $employeeID) {
        $overtime->refresh();

        $isEmployeeFinishedAttendance = true;

        foreach ($overtime->overtimeEmployees as $overtimeEmployee) {
            if (is_null($overtimeEmployee->check_in_time) || is_null($overtimeEmployee->check_out_time)) {
                $isEmployeeFinishedAttendance = false;
            }
        }

        $isFinished = $overtime->last_status == OvertimeHistory::TypeApproved && $isEmployeeFinishedAttendance;
        if ($isFinished) {
            $overtime->last_status = OvertimeHistory::TypeFinished;
            $overtime->last_status_at = Carbon::now();
            $overtime->save();

            $overtimeHistory = new OvertimeHistory();
            $overtimeHistory->overtime_id = $overtime->id;
            $overtimeHistory->employee_id = $employeeID;
            $overtimeHistory->history_type = OvertimeHistory::TypeFinished;
            $overtimeHistory->save();
        }
    }

    public function getActiveOvertime(Request $request, int $id) {
        /**
         * @var User $user
         */
        $user = $request->user();

        /**
         * @var OvertimeEmployee
         */
        $overtimeEmployee = OvertimeEmployee::query()
            ->with(['overtimeDate.overtime'])
            ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
            ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
            ->where(function(Builder $builder) {
                $builder->orWhereNull('overtime_employees.check_in_time')
                    ->orWhereNull('overtime_employees.check_out_time');
            })
            ->where('overtime_employees.employee_id', '=', $user->employee_id)
            ->where('overtimes.id', '=', $id)
            ->whereNotIn('overtimes.last_status', [OvertimeHistory::TypeRejected])
            ->orderBy('overtime_dates.start_time', 'ASC')
            ->select(['overtime_employees.*'])
            ->first();

        if (!$overtimeEmployee) {
            return response()->json([
                'status' => false,
                'message' => "You don't have any overtime date"
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => true,
            'message' => "Success",
            'data' => $overtimeEmployee
        ], ResponseAlias::HTTP_OK);
    }

    public function monthlyEvaluate(Request $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();
            $clientTimezone = getClientTimezone();

            $query = OvertimeEmployee::query()
                ->with(['employee:employees.id,name', 'overtimeDate.overtime.unit', 'employeeAttendance'])
                ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
                ->join('overtimes', 'overtimes.id', '=', 'overtime_dates.overtime_id')
                ->where('overtime_employees.employee_id', '=', $user->employee_id)
                ->select(['overtime_employees.*'])
                ->orderBy('overtimes.start_date', 'DESC')
                ->where('overtimes.last_status', '!=', OvertimeHistory::TypeRejected);

            if ($monthly = $request->query('monthly')) {
                $query->whereRaw("TO_CHAR((overtime_dates.start_time::timestamp without time zone at time zone 'UTC' at time zone '$clientTimezone'), 'YYYY-mm') = ?", [$monthly]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Succcess fetch data',
                'data' => [
                    'meta' => [
                        'full_attendance' => (clone $query)->whereNotNull('overtime_employees.check_in_time')->whereNotNull('overtime_employees.check_out_time')->count(),
                        'late_check_in' => (clone $query)->whereRaw('overtime_employees.check_in_time > overtime_dates.start_time')->count(),
                        'not_check_out' => (clone $query)->whereNull('overtime_employees.check_out_time')->count(),
                        'early_check_out' => (clone $query)->whereRaw('overtime_employees.check_out_time < overtime_dates.end_time')->count(),
                        'not_attendance' => (clone $query)->whereNull('overtime_employees.check_in_time')->whereNull('overtime_employees.check_out_time')->count(),
                        'total_schedule' => (clone $query)->count()
                    ],
                    'data' => $this->list($query, $request)
                ],
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteOvertime(Request $request, int $id) {
        try {
            $user = $request->user();

            /**
             * @var Overtime $overtime
             */
            $overtime = Overtime::query()->where('id', '=', $id)->first();
            if(!$overtime) {
                return response()->json([
                    'status' => false,
                    'message' => 'Overtime not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($overtime->last_status == OvertimeApproval::StatusApproved) {
                return response()->json([
                    'status' => false,
                    'message' => 'Overtime already approved!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            OvertimeEmployee::query()
                ->join('overtime_dates', 'overtime_dates.id', '=', 'overtime_employees.overtime_date_id')
                ->where('overtime_dates.overtime_id', '=', $overtime->id)
                ->delete();

            OvertimeDate::query()
                ->where('overtime_dates.overtime_id', '=', $overtime->id)
                ->delete();

            $overtime->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Success"
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
