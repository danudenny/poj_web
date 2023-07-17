<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
use App\Jobs\SyncEmployeesJob;
use App\Models\Employee;
use App\Models\Unit;
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
 }
