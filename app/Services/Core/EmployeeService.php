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
            $employees = Employee::query()->with(['corporate', 'kanwil', 'area', 'cabang', 'outlet', 'job', 'units']);
            $employeesData = [];

            $highestPriorityRole = null;
            $highestPriority = null;

            foreach ($auth->roles as $role) {
                if ($highestPriority === null || $role['priority'] < $highestPriority) {
                    $highestPriorityRole = $role;
                    $highestPriority = $role['priority'];
                }
            }

            $employees->when($request->filled('corporate'), function(Builder $builder) use ($request) {
                $builder->whereHas('corporate', function (Builder $builder) use ($request) {
                    $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(request()->query('corporate')).'%']);
                });
            });
            $employees->when($request->filled('corporate_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('corporate', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('corporate_id'));
                });
            });

            $employees->when($request->filled('kanwil'), function(Builder $builder) use ($request) {
                $builder->whereHas('kanwil', function (Builder $builder) use ($request) {
                    $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(request()->query('kanwil')).'%']);
                });
            });
            $employees->when($request->filled('kanwil_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('kanwil', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('kanwil_id'));
                });
            });

            $employees->when($request->filled('area'), function(Builder $builder) use ($request) {
                $builder->whereHas('area', function (Builder $builder) use ($request) {
                    $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(request()->query('area')).'%']);
                });
            });
            $employees->when($request->filled('area_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('area', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('area_id'));
                });
            });

            $employees->when($request->filled('cabang'), function(Builder $builder) use ($request) {
                $builder->whereHas('cabang', function (Builder $builder) use ($request) {
                    $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(request()->query('cabang')).'%']);
                });
            });
            $employees->when($request->filled('cabang_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('cabang', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('cabang_id'));
                });
            });

            $employees->when($request->filled('outlet'), function(Builder $builder) use ($request) {
                $builder->whereHas('outlet', function (Builder $builder) use ($request) {
                    $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(request()->query('outlet')).'%']);
                });
            });
            $employees->when($request->filled('outlet_id'), function(Builder $builder) use ($request) {
                $builder->whereHas('outlet', function (Builder $builder) use ($request) {
                    $builder->where('id', '=', $request->query('outlet_id'));
                });
            });

            $employees->when($request->filled('job'), function(Builder $builder) use ($request) {
                $builder->whereHas('job', function (Builder $builder) use ($request) {
                    $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(request()->query('job')).'%']);
                });
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

            if ($highestPriorityRole->role_level === 'superadmin') {
                $employeesData = $employees->paginate($request->get('per_page', 10));
            } else if ($highestPriorityRole->role_level === 'staff') {
                $employeesData = $employees->where('id', '=', $auth->employee_id);
            } else if ($highestPriorityRole->role_level === 'admin') {
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

    public function listPaginatedEmployees(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        try {
            $employees = Employee::query()->with(['corporate','kanwil', 'area', 'cabang', 'outlet', 'job']);

            $lastUnitRelationID = $request->get('last_unit_relation_id');
            $unitRelationID = $request->get('unit_relation_id');

            $employees->when($request->unit_id, function (Builder $builder) use ($request) {
                $builder->where('unit_id', '=', $request->unit_id);
            });

            if ($user->isHighestRole(Role::RoleAdmin)) {
                if (!$unitRelationID) {
                    $defaultUnitRelationID = $user->employee->getLastUnitID();

                    if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                        $defaultUnitRelationID = $requestUnitRelationID;
                    }

                    $unitRelationID = $defaultUnitRelationID;
                }
            } else if ($user->isHighestRole(Role::RoleStaff)) {
                if (!$lastUnitRelationID) {
                    $lastUnitRelationID = $user->employee->getLastUnitID();
                }
            }

            $employees->when($request->input('job_id'), function (Builder $builder) use ($request) {
                $builder->leftJoin('jobs', 'jobs.odoo_job_id', '=', 'employees.job_id')
                    ->where('jobs.id', '=', $request->input('job_id'));
            });

            $employees->when($request->input('name'), function (Builder $builder) use ($request) {
                $builder->where('employees.name', "ILIKE", "%" . $request->input('name') . "%");
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
