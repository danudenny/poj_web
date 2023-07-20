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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        try {
            $employees = Employee::query()->with(['kanwil', 'area', 'cabang', 'outlet', 'job']);
            $employeesData = [];

            $highestPriorityRole = null;
            $highestPriority = null;

            foreach ($auth->roles as $role) {
                if ($highestPriority === null || $role['priority'] < $highestPriority) {
                    $highestPriorityRole = $role;
                    $highestPriority = $role['priority'];
                }
            }

            $employees->when($request->get('kanwil_id'), function ($query) use ($request) {
                $query->where('kanwil_id', '=', $request->get('kanwil_id'));
            });
            $employees->when($request->get('area_id'), function ($query) use ($request) {
                $query->where('area_id', '=', $request->get('area_id'));
            });
            $employees->when($request->get('cabang_id'), function ($query) use ($request) {
                $query->where('cabang_id', '=', $request->get('cabang_id'));
            });
            $employees->when($request->get('outlet_id'), function ($query) use ($request) {
                $query->where('outlet_id', '=', $request->get('outlet_id'));
            });

            if ($highestPriorityRole->role_level === 'superadmin') {
                $employeesData = $employees->paginate($request->get('limit', 10));
            } else if ($highestPriorityRole->role_level === 'staff') {
                $employeesData = $employees->where('id', '=', $auth->employee_id);
            } else if ($highestPriorityRole->role_level === 'admin') {
                $empUnit = $auth->employee->getRelatedUnit();
                $flatUnit = UnitHelper::flattenUnits($empUnit);
                $relationIds = array_column($flatUnit, 'relation_id');

                $employeesData[] = $employees
                    ->whereIn('kanwil_id', $relationIds)
                    ->orWhereIn('area_id', $relationIds)
                    ->orWhereIn('cabang_id', $relationIds)
                    ->orWhereIn('outlet_id', $relationIds)
                    ->with(['job', 'kanwil', 'area', 'cabang', 'outlet'])
                    ->paginate(10);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => is_array($employeesData) ? $employeesData[0] : $employeesData
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
            $employee = Employee::with( ['kanwil', 'area', 'cabang', 'outlet', 'job', 'timesheetSchedules'])->find($id);

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
        return response()->json(['message' => 'Success']);

    }

    public function update($request, $id) {
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

    public function listPaginatedEmployees(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        try {
            $employees = Employee::query()->with(['kanwil', 'area', 'cabang', 'outlet', 'job']);

            $lastUnitRelationID = $request->get('last_unit_relation_id');
            $unitRelationID = $request->get('unit_relation_id');

            if ($user->isHighestRole(Role::RoleAdmin)) {
                if (!$unitRelationID) {
                    $unitRelationID = $user->employee->getLastUnitID();
                }
            } else if ($user->isHighestRole(Role::RoleStaff)) {
                $lastUnitRelationID = $user->employee->getLastUnitID();
            }

            $employees->when($request->input('job_id'), function (Builder $builder) use ($request) {
                $builder->leftJoin('jobs', 'jobs.odoo_job_id', '=', 'employees.job_id')
                    ->where('jobs.id', '=', $request->input('job_id'));
            });

            $employees->when($lastUnitRelationID, function (Builder $builder) use ($lastUnitRelationID) {
                $builder->where(function(Builder $builder) use ($lastUnitRelationID) {
                    $builder->orWhere(function(Builder $builder) use ($lastUnitRelationID) {
                        $builder->where('outlet_id', '=', $lastUnitRelationID);
                    })
                        ->orWhere(function(Builder $builder) use ($lastUnitRelationID) {
                            $builder->where('outlet_id', '=', 0)
                                ->where('cabang_id', '=', $lastUnitRelationID);
                        })
                        ->orWhere(function(Builder $builder) use ($lastUnitRelationID) {
                            $builder->where('outlet_id', '=', 0)
                                ->where('cabang_id', '=', 0)
                                ->where('area_id', '=', $lastUnitRelationID);
                        })
                        ->orWhere(function(Builder $builder) use ($lastUnitRelationID) {
                            $builder->where('outlet_id', '=', 0)
                                ->where('cabang_id', '=', 0)
                                ->where('area_id', '=', 0)
                                ->where('kanwil_id', '=', $lastUnitRelationID);
                        })
                        ->orWhere(function(Builder $builder) use ($lastUnitRelationID) {
                            $builder->where('outlet_id', '=', 0)
                                ->where('cabang_id', '=', 0)
                                ->where('area_id', '=', 0)
                                ->where('kanwil_id', '=', 0)
                                ->where('corporate_id', '=', $lastUnitRelationID);
                        });
                });
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
