<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
use App\Jobs\SyncEmployeesJob;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeService extends BaseService
{
    /**
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        $auth = Auth::user();
        $roleLevel = $request->header('X-Selected-Role');

        try {
            $employees = Employee::query()->with(['department', 'team', 'corporate', 'kanwil', 'area', 'cabang', 'outlet', 'job', 'units', 'partner']);
            $employeesData = [];

            $employees->when($request->filled('department_id'), function(Builder $builder) use ($request) {
                $builder->where('department_id', '=', $request->query('department_id'));
            });

            $employees->when($request->filled('unit_level'), function(Builder $builder) use ($request) {
                $builder->whereHas('units', function (Builder $builder) use ($request) {
                    $builder->where('unit_level', '=', $request->query('unit_level'));
                });
            });

            $employees->when($request->filled('team_id'), function(Builder $builder) use ($request) {
                $builder->where('team_id', '=', intval($request->input('team_id')));
            });

            $employees->when($request->filled('corporate_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('corporate', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('corporate_id'));
                });
            });

            $employees->when($request->filled('employee_category'), function(Builder $builder) use ($request) {
                $builder->where('employee_category', '=', $request->query('employee_category'));
            });

            $employees->when($request->filled('employee_type'), function(Builder $builder) use ($request) {
                $builder->where('employee_type', '=', $request->query('employee_type'));
            });

            $employees->when($request->filled('kanwil_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('kanwil', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('kanwil_id'));
                });
            });

            $employees->when($request->filled('area_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('area', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('area_id'));
                });
            });

            $employees->when($request->filled('cabang_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('cabang', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('cabang_id'));
                });
            });

            $employees->when($request->filled('outlet_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('outlet', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('outlet_id'));
                });
            });

            $employees->when($request->filled('default_operating_unit_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('operatingUnit', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('default_operating_unit_id'));
                });
            });

            $employees->when($request->filled('job_id'), function(Builder $builder) use ($request) {
                $builder->where('job_id', '=', $request->query('job_id'));
            });

            $employees->when($request->filled('unit_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('units', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('unit_id'));
                });
            });

            $employees->when($request->filled('unit_level'), function(Builder $builder) use ($request) {
                $builder->whereHas('units', function (Builder $builder) use ($request) {
                    $builder->where('unit_level', '=', $request->query('unit_level'));
                });
            });

            $employees->when($request->filled('name'), function(Builder $builder) use ($request) {
                $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(request()->query('name')).'%']);
            });

            $employees->when($request->filled('email'), function(Builder $builder) use ($request) {
                $builder->whereRaw('LOWER(work_email) LIKE ?', ['%'.strtolower(request()->query('email')).'%']);
            });

            if ($roleLevel === Role::RoleSuperAdministrator) {
                $employeesData = $employees->paginate($request->get('per_page', 10));
            } else if ($roleLevel === Role::RoleStaff) {
                $employeesData = $employees->where('id', '=', $auth->employee_id)->first();
            } else if ($roleLevel === Role::RoleAdmin) {
                $empUnit = $auth->employee->getRelatedUnit();

                $relationIds = [];

                if ($requestRelationID = $this->getRequestedUnitID()) {
                    $relationIds[] = $requestRelationID;
                } else {
                    $flatUnit = UnitHelper::flattenUnits($empUnit);
                    $relationIds = array_column($flatUnit, 'relation_id');
                }

                $employeesData[] = $employees
                    ->whereIn('corporate_id', $relationIds)
                    ->orWhereIn('kanwil_id', $relationIds)
                    ->orWhereIn('area_id', $relationIds)
                    ->orWhereIn('cabang_id', $relationIds)
                    ->orWhereIn('outlet_id', $relationIds)
                    ->with(['job', 'corporate', 'kanwil', 'area', 'cabang', 'outlet'])
                    ->paginate($request->get('per_page', 10));
            } else {
                $employeesData = $employees->paginate($request->get('per_page', 10));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => is_array($employeesData) ? reset($employeesData) : $employeesData,
            ]);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e);
        }
    }

    /**
     * @throws Exception
     */
    public function view($id): Model|Builder
    {
        try {
            $employee = Employee::with([
                'partner', 'department', 'team', 'corporate', 'kanwil', 'area', 'cabang',
                'outlet', 'job', 'timesheetSchedules', 'masterOvertimeLimit',
                'workingHour.workingHourDetails'
            ])->find($id);

            if (!$employee) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return $employee;

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    public function syncToUser(): JsonResponse
    {
        dispatch(new SyncEmployeesJob());
        return response()->json(['message' => 'Your Synchronization is running on background. Refresh after 5 minutes to see the result.']);
    }

    public function update($request, $id): JsonResponse
    {
        $empExists = Employee::find($id);
        if (!$empExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ]);
        }

        $unitExists = Unit::find($request->unit_id);
        if (!$unitExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit not found'
            ]);
        }

        DB::beginTransaction();
        try {
            $empExists->update($request->all());
            $empExists->unit_id = $request->unit_id;

            if (!$empExists->save()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Employee failed to update'
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Employee updated successfully',
                'data' => $empExists
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Employee failed to update',
                'data' => $e->getMessage()
            ]);
        }
    }

    public function listPaginatedEmployees(Request $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        try {
            $employees = Employee::query()->with(['department', 'operatingUnit', 'corporate', 'kanwil', 'area', 'cabang', 'outlet', 'job', 'units', 'partner', 'team']);
            $employees->leftJoin('jobs', 'jobs.odoo_job_id', '=', 'employees.job_id');

            $lastUnitRelationID = $request->get('last_unit_relation_id');
            $unitRelationID = $request->get('unit_relation_id');
            $unitID = $request->get('unit_id');
            $defaultOperatingUnit = $request->get('default_operating_unit_id', '');
            $odooJobID = $request->get('odoo_job_id');

            $employees->when($request->filled('unit_level'), function(Builder $builder) use ($request) {
                $builder->join('units AS unitLevel', 'unitLevel.relation_id', '=', 'employees.unit_id');
                $builder->whereIn('unitLevel.unit_level', explode(",", $request->query('unit_level')));
            });

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
                $employees->leftJoin('user_operating_units', 'user_operating_units.unit_relation_id', '=', 'employees.default_operating_unit_id');
                $employees->where(function (Builder $builder) use ($user) {
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
                $employees->join(DB::raw($subQuery), function (JoinClause $joinClause) {
                    $joinClause->on(DB::raw("relatedJob.odoo_job_id"), '=', DB::raw('employees.job_id'))
                        ->where(DB::raw("relatedJob.unit_relation_id"), '=', DB::raw('employees.unit_id'));
                });

                $employees->where(function (Builder $builder) use ($user) {
                    $builder->orWhere(function(Builder $builder) use ($user) {
                        $builder->where('employees.job_id', '=', $user->employee->job_id)
                            ->where('employees.unit_id', '=', $user->employee->unit_id)
                            ->where('employees.id', '=', $user->employee_id);
                    })->orWhere(function (Builder $builder) use ($user) {
                        $builder->orWhere('employees.job_id', '!=', $user->employee->job_id)
                            ->orWhere('employees.unit_id', '!=', $user->employee->unit_id);
                    });
                });
            }

            $employees->when($request->input('job_id'), function (Builder $builder) use ($request) {
                $builder->where('jobs.id', '=', $request->input('job_id'));
            });

            if ($isOperatingUnitUser = $request->input('is_operating_unit_user')) {
                if ($isOperatingUnitUser == '1') {
                    $employees->where('employees.default_operating_unit_id', '>', 0);
                }
            }

            if ($email = $request->get('work_email')) {
                $employees->where('employees.work_email', 'ILIKE', "%$email%");
            }

            if ($employeeCategory = $request->get('employee_category')) {
                $employeeCategory = strtolower(str_replace(" ", "_", $employeeCategory));
                $employees->where('employees.employee_category', 'ILIKE', "%$employeeCategory%");
            }

            if ($jobName = $request->get('job_name')) {
                $employees->where('jobs.name', 'ILIKE', "%$jobName%");
            }

            if ($defaultOperatingUnit) {
                $employees->whereIn('employees.default_operating_unit_id', explode(",", $defaultOperatingUnit));
            }

            if ($odooJobID) {
                $employees->where('employees.job_id', '=', $odooJobID);
            }

            $employees->when($request->input('name'), function (Builder $builder) use ($request) {
                $builder->where('employees.name', "ILIKE", "%" . $request->input('name') . "%");
            });

            if ($unitID) {
                $employees->where('employees.unit_id', '=', $unitID);
            }

            $employees->when($lastUnitRelationID, function (Builder $builder) use ($lastUnitRelationID) {
                $builder->where('employees.unit_id', '=', $lastUnitRelationID);
            });

            $employees->when($unitRelationID, function(Builder $builder) use ($unitRelationID) {
                $builder->where(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                        ->orWhere('employees.cabang_id', '=', $unitRelationID)
                        ->orWhere('employees.area_id', '=', $unitRelationID)
                        ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                        ->orWhere('employees.corporate_id', '=', $unitRelationID);
                });
            });

            $employees->when($request->filled('odoo_department_id'), function(Builder $builder) use ($request) {
                $builder->where('employees.department_id', '=', $request->query('odoo_department_id'));
            });

            $employees->when($request->filled('department_id'), function(Builder $builder) use ($request) {
                $builder->join('departments', 'departments.odoo_department_id', '=', 'employees.department_id');
                $builder->where('departments.id', '=', $request->query('department_id'));
            });

            $employees->when($request->filled('team_id'), function(Builder $builder) use ($request) {
                $builder->where('employees.team_id', '=', intval($request->input('team_id')));
            });

            $employees->when($request->filled('employee_type'), function(Builder $builder) use ($request) {
                $builder->where('employees.employee_type', '=', $request->query('employee_type'));
            });

            $employees->when($request->filled('kanwil_name'), function(Builder $builder) use ($request) {
                $builder->join('units AS kanwil_units', 'kanwil_units.relation_id', '=', 'employees.kanwil_id');
                $builder->where('kanwil_units.unit_level', '=', Unit::UnitLevelKanwil);
                $builder->whereRaw('kanwil_units.name ILIKE ?', ["%" . strtolower($request->query('kanwil_name')) . "%"]);
            });

            $employees->when($request->filled('area_name'), function(Builder $builder) use ($request) {
                $builder->join('units AS area_units', 'area_units.relation_id', '=', 'employees.area_id');
                $builder->where('area_units.unit_level', '=', Unit::UnitLevelArea);
                $builder->whereRaw('area_units.name ILIKE ?', ["%" . strtolower($request->query('area_name')) . "%"]);
            });

            $employees->when($request->filled('cabang_name'), function(Builder $builder) use ($request) {
                $builder->join('units AS cabang_units', 'cabang_units.relation_id', '=', 'employees.cabang_id');
                $builder->where('cabang_units.unit_level', '=', Unit::UnitLevelCabang);
                $builder->whereRaw('cabang_units.name ILIKE ?', ["%" . strtolower($request->query('cabang_name')) . "%"]);
            });

            $employees->when($request->filled('outlet_name'), function(Builder $builder) use ($request) {
                $builder->join('units AS outlet_units', 'outlet_units.relation_id', '=', 'employees.outlet_id');
                $builder->where('outlet_units.unit_level', '=', Unit::UnitLevelOutlet);
                $builder->whereRaw('outlet_units.name ILIKE ?', ["%" . strtolower($request->query('outlet_name')) . "%"]);
            });

            $employees->when($request->filled('customer_name'), function(Builder $builder) use ($request) {
                $builder->join('partners AS partner_name', 'partner_name.id', '=', 'employees.customer_id');
                $builder->whereRaw('partner_name.name ILIKE ?', ["%" . strtolower($request->query('customer_name')) . "%"]);
            });

            $employees->select(['employees.*']);
            $employees->groupBy(['employees.id']);

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => $this->list($employees, $request)
            ]);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e);
        }
    }
 }
