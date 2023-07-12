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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeService extends BaseService
{
    /**
     * @throws Exception
     */
    public function index($request): JsonResponse
    {
        try {
            $loggedInEmployee = Auth::user();

            $employees = Employee::query();
            if ($loggedInEmployee->hasRole('superadmin')) {
                $employees = $employees->with(['kanwil', 'area', 'cabang', 'outlet', 'job', 'employeeDetail', 'employeeDetail.employeeTimesheet'])
                    ->when(request()->filled('name'), function ($query) {
                        $query->whereRaw('LOWER("name") LIKE ? ', '%'.strtolower(request()->query('name')).'%');
                    })
                    ->when(request()->filled('unit_id'), function ($query) {
                        $query->where('kanwil_id', request()->query('unit_id'));
                        $query->orWhere('area_id', request()->query('unit_id'));
                        $query->orWhere('cabang_id', request()->query('unit_id'));
                        $query->orWhere('outlet_id', request()->query('unit_id'));
                    })
                    ->paginate(10);
            } else {
                $employees = $employees->with(['kanwil', 'area', 'cabang', 'outlet', 'job', 'employeeDetail', 'employeeDetail.employeeTimesheet'])
                    ->when(request()->filled('name'), function ($query) {
                        $query->whereRaw('LOWER("name") LIKE ? ', '%'.strtolower(request()->query('name')).'%');
                    })
                    ->where('kanwil_id', $loggedInEmployee->employee->kanwil_id)
                    ->where('area_id', $loggedInEmployee->employee->area_id)
                    ->where('cabang_id', $loggedInEmployee->employee->cabang_id)
                    ->where('outlet_id', $loggedInEmployee->employee->outlet_id)
                    ->order_by('name', 'asc')
                    ->paginate(10);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => $employees
            ]);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function view($id): Model|Builder
    {
        try {
            $employee = Employee::with( ['kanwil', 'area', 'cabang', 'outlet', 'job', 'employeeDetail', 'employeeDetail.employeeTimesheet'])->find($id);

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
