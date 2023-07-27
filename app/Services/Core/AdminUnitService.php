<?php

namespace App\Services\Core;

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
            $query = AdminUnit::query()->with(['unit', 'employee:employees.id,name']);
            $query->where('is_active', '=', true);
            $query->orderBy('id', 'DESC');

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
}
