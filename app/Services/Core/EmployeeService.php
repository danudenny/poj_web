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
            } else if ($this->isRequestedRoleLevel(Role::RoleAdminOperatingUnit)) {

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
            $employee = Employee::with( ['partner', 'department', 'team', 'corporate', 'kanwil', 'area', 'cabang', 'outlet', 'job', 'timesheetSchedules'])->find($id);

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
        try {
            Employee::chunk(1000, function ($employees) {
                $userInsertData = [];
                $userIdsToUpdate = [];
                $employeeIds = $employees->pluck('id')->toArray();
                $existingEmails = User::whereIn('employee_id', $employeeIds)->pluck('email')->toArray();

                foreach ($employees as $employee) {
                    if (!filter_var($employee->work_email, FILTER_VALIDATE_EMAIL) || in_array($employee->work_email, $existingEmails)) {
                        continue;
                    }

                    $userInsertData[] = [
                        'name' => $employee->name,
                        'email' => $employee->work_email,
                        'employee_id' => $employee->id,
                        'email_verified_at' => now(),
                        'password' => '$2y$10$m54GoOajOHJ4AYs2VnfP7e3hPBf3pJw.Omimsct0m6gDcHCt8hTHi',
                        'is_active' => true,
                        'is_new' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $userIdsToUpdate[] = $employee->id;
                }

                User::upsert($userInsertData, ['id'], ['name', 'email', 'employee_id', 'email_verified_at', 'password', 'is_active', 'is_new', 'created_at', 'updated_at']);
            });
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to sync users',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Users synced successfully',
        ], 201);
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

            $lastUnitRelationID = $request->get('last_unit_relation_id');
            $unitRelationID = $request->get('unit_relation_id');

            $employees->when($request->filled('unit_level'), function (Builder $builder) use ($request) {
                $builder->whereHas('units', function (Builder $builder) use ($request) {
                    $builder->where('unit_level', '=', $request->query('unit_level'));
                });
            });

            if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
                if (!$unitRelationID) {
                    $defaultUnitRelationID = $user->employee->getLastUnitID();

                    if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                        $defaultUnitRelationID = $requestUnitRelationID;
                    }

                    $unitRelationID = $defaultUnitRelationID;
                }
            } else if ($this->isRequestedRoleLevel(Role::RoleStaff)) {
                if (!$lastUnitRelationID) {
                    $employees->where('id', '=', $user->employee_id);
                }
            } else if ($this->isRequestedRoleLevel(Role::RoleAdminOperatingUnit)) {
                if (!$lastUnitRelationID) {
                    $lastUnitRelationID = implode(",", $user->listOperatingUnitIDs());
                }
            }

            $employees->when($request->input('job_id'), function (Builder $builder) use ($request) {
                $builder->leftJoin('jobs', 'jobs.odoo_job_id', '=', 'employees.job_id')
                    ->where('jobs.id', '=', $request->input('job_id'));
            });

            $employees->when($request->filled('odoo_job_id'), function(Builder $builder) use ($request) {
                $builder->where('employees.job_id', '=', $request->query('odoo_job_id'));
            });

            $employees->when($request->input('name'), function (Builder $builder) use ($request) {
                $builder->where('employees.name', "ILIKE", "%" . $request->input('name') . "%");
            });

            $employees->when($request->input('unit_id'), function (Builder $builder) use ($request) {
                $builder->where('employees.unit_id', '=', $request->input('unit_id'));
            });

            $employees->when($lastUnitRelationID, function (Builder $builder) use ($lastUnitRelationID) {
                $builder->where('employees.unit_id', '=', $lastUnitRelationID);
            });

            $employees->when($unitRelationID, function(Builder $builder) use ($unitRelationID) {
                $builder->where(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('outlet_id', '=', $unitRelationID)
                        ->orWhere('cabang_id', '=', $unitRelationID)
                        ->orWhere('area_id', '=', $unitRelationID)
                        ->orWhere('kanwil_id', '=', $unitRelationID)
                        ->orWhere('corporate_id', '=', $unitRelationID);
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

            $employees->when($request->filled('employee_category'), function(Builder $builder) use ($request) {
                $builder->where('employees.employee_category', '=', $request->query('employee_category'));
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
