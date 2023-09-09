<?php

namespace App\Services\Core;

use App\Http\Requests\AdminUnit\AssignMultipleUnitRequest;
use App\Http\Requests\AdminUnit\CreateAdminUnitRequest;
use App\Http\Requests\AdminUnit\RemoveAdminUnitRequest;
use App\Models\AdminUnit;
use App\Models\Employee;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AdminUnitService extends BaseService
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) {
        try {
            $query = AdminUnit::query()->with(['unit', 'employee:employees.id,name,work_email']);
            $query->where('admin_units.is_active', '=', true);
            $query->orderBy('admin_units.id', 'DESC');
            $query->select(['admin_units.*']);

            if ($employeeName = $request->input('employee_name')) {
                $query->join('employees', 'employees.id', '=', 'admin_units.employee_id');
                $query->whereRaw('employees.name ILIKE ?', ["%" . $employeeName . "%"]);
            }

            if ($employeeEmail = $request->input('employee_email')) {
                $query->join('employees', 'employees.id', '=', 'admin_units.employee_id');
                $query->whereRaw('employees.work_email ILIKE ?', ["%" . $employeeEmail . "%"]);
            }

            if ($unitName = $request->input('unit_name')) {
                $query->join('units', 'units.relation_id', '=', 'admin_units.unit_relation_id');
                $query->whereRaw('units.name ILIKE ?', ["%" . $unitName . "%"]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => $this->list($query, $request)
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param CreateAdminUnitRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function insert(CreateAdminUnitRequest $request) {
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
                    'message' => 'Unit Not Found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var Employee $employee
             */
            $employee = Employee::query()->where('id', '=', $request->input('employee_id'))->first();
            if (!$employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee Not Found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $adminUnit = AdminUnit::query()
                ->where('employee_id', '=', $employee->id)
                ->where('unit_relation_id', '=', $unit->relation_id)
                ->first();

            DB::beginTransaction();

            if (!$adminUnit) {
                $adminUnit = new AdminUnit();
                $adminUnit->employee_id = $employee->id;
                $adminUnit->unit_relation_id = $unit->relation_id;
            }

            $adminUnit->is_active = true;
            $adminUnit->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assignMultipleUnit(AssignMultipleUnitRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();


            $unitRelationIDs = $request->input('unit_relation_ids', []);
            $totalExistingUnit = Unit::query()->whereIn('relation_id', $unitRelationIDs)->count();
            if ($totalExistingUnit < count($unitRelationIDs)) {
                return response()->json([
                    'status' => false,
                    'message' => sprintf("%s Unit Not Found!", (count($unitRelationIDs) - $totalExistingUnit))
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var Employee $employee
             */
            $employee = Employee::query()->where('id', '=', $request->input('employee_id'))->first();
            if (!$employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee Not Found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            foreach ($unitRelationIDs as $unitRelationID) {
                $adminUnit = AdminUnit::query()
                    ->where('employee_id', '=', $employee->id)
                    ->where('unit_relation_id', '=', $unitRelationID)
                    ->first();
                if (!$adminUnit) {
                    $adminUnit = new AdminUnit();
                    $adminUnit->employee_id = $employee->id;
                    $adminUnit->unit_relation_id = $unitRelationID;
                }

                $adminUnit->is_active = true;
                $adminUnit->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param RemoveAdminUnitRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(RemoveAdminUnitRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var AdminUnit $adminUnit
             */
            $adminUnit = AdminUnit::query()->where('id', '=', $id)->first();
            if (!$adminUnit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Admin Unit Not Found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $adminUnit->is_active = false;
            $adminUnit->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function myAdminUnit(Request $request) {
        try {
            /**
             * @var Employee $employee
             */
            $employee = $request->user()->employee;

            $activeAdminUnit = [];
            $activeAdminUnit[] = [
                'unit_relation_id' => $employee->getLastUnit()->relation_id,
                'name' => $employee->getLastUnit()->name . " (Default)",
                'formatted_name' => $employee->getLastUnit()->formatted_name
            ];

            /**
             * @var AdminUnit[] $adminUnits
             */
            $adminUnits = AdminUnit::query()
                ->where('employee_id', '=', $employee->id)
                ->where('is_active', '=', true)
                ->get();

            foreach ($adminUnits as $adminUnit) {
                $activeAdminUnit[] = [
                    'unit_relation_id' => $adminUnit->unit_relation_id,
                    'name' => $adminUnit->unit->name,
                    'formatted_name' => $adminUnit->unit->formatted_name
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => $activeAdminUnit
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
