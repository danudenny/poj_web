<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
use App\Http\Requests\Approval\CreateApprovalRequest;
use App\Http\Requests\Approval\UpdateApprovalRequest;
use App\Models\Approval;
use App\Models\ApprovalModule;
use App\Models\ApprovalUser;
use App\Models\Employee;
use App\Models\Unit;
use App\Models\UnitHasJob;
use App\Models\User;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ApprovalService extends BaseService
{
    public function index($request): JsonResponse
    {
        $user = auth()->user();
        $roles = $user->roles;
        $lastUnit = $user->employee->last_unit;
        $highestRoles = $roles->sortBy('priority')->first();

        try {
            $approvals = Approval::query();
            $approvals->with(['approvalModule', 'approvalUsers']);
            $approvals->when($request->name, function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->name . '%');
            });
            $approvals->when($request->approval_module_id, function($q) use ($request) {
                $q->where('approval_module_id', $request->approval_module_id);
            });
            $approvals->when($request->unit_id, function($q) use ($request) {
                $q->where('unit_id', $request->unit_id);
            });

             if ($highestRoles->role_level === 'admin') {
                $empUnit = $user->employee->getRelatedUnit();
                $lastUnit = $user->employee->getLastUnit();
                $empUnit[] = $lastUnit;
                $flatUnit = UnitHelper::flattenUnits($empUnit);
                $relationIds = array_column($flatUnit, 'relation_id');
                $relationIds[] = $lastUnit->id;
                $approvals->whereIn('unit_id', $relationIds);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully.',
                'data' => $approvals->paginate(request()->get('size') ?? 10),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data failed to retrieve.',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function save(CreateApprovalRequest $request): JsonResponse
    {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $arg = [
                'unit_relation_id' => $request->input('unit_relation_id'),
                'unit_level' => $request->input('unit_level'),
                'name' => $request->input('name'),
                'approval_module_id' => $request->input('approval_module_id'),
                'approvers' => $request->input('approvers', []),
                'department_id' => $request->input('department_id'),
                'team_id' => $request->input('team_id'),
                'odoo_job_id' => $request->input('odoo_job_id'),
            ];

            $isApprovalExist = Approval::query()
                ->where('unit_relation_id', '=', $arg['unit_relation_id'])
                ->where('unit_level', '=', $arg['unit_level'])
                ->where('approval_module_id', '=', $arg['approval_module_id']);

            if ($arg['department_id']) {
                $isApprovalExist->where('department_id', '=', $arg['department_id']);
            } else {
                $isApprovalExist->whereNull('department_id');
            }

            if ($arg['team_id']) {
                $isApprovalExist->where('team_id', '=', $arg['team_id']);
            } else {
                $isApprovalExist->whereNull('team_id');
            }

            if ($arg['odoo_job_id']) {
                $isApprovalExist->where('odoo_job_id', '=', $arg['odoo_job_id']);
            } else {
                $isApprovalExist->whereNull('odoo_job_id');
            }

            if ($isApprovalExist->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data already exists.',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $approvalModuleExists = ApprovalModule::query()
                ->where('id', '=', $arg['approval_module_id'])
                ->exists();
            if (!$approvalModuleExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Approval module not found.',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $isUnitExist = Unit::query()
                ->where('relation_id', '=', $arg['unit_relation_id'])
                ->where('unit_level', '=', $arg['unit_level'])
                ->exists();
            if (!$isUnitExist) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit not found.',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var ApprovalUser[] $approvalUsers
             */
            $approvalUsers = [];

            foreach ($arg['approvers'] as $approver) {
                $unitExis = Unit::query()
                    ->where('relation_id', '=', $approver['unit_relation_id'])
                    ->where('unit_level', '=', $approver['unit_level'])
                    ->exists();
                if (!$unitExis) {
                    return response()->json([
                        'status' => false,
                        'message' => 'One of unit is not exist.',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                $employeeExist = Employee::query()
                    ->where('id', '=', $approver['employee_id'])
                    ->exists();
                if (!$employeeExist) {
                    return response()->json([
                        'status' => false,
                        'message' => 'One of employee is not exist.',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                $approvalUser = new ApprovalUser();
                $approvalUser->employee_id = $approver['employee_id'];
                $approvalUser->unit_relation_id = $approver['unit_relation_id'];
                $approvalUser->unit_level = $approver['unit_level'];
                $approvalUser->department_id = $approver['department_id'];
                $approvalUser->team_id = $approver['team_id'];
                $approvalUser->odoo_job_id = $approver['odoo_job_id'];

                $approvalUsers[] = $approvalUser;
            }

            DB::beginTransaction();

            $approval = new Approval();
            $approval->unit_relation_id = $arg['unit_relation_id'];
            $approval->unit_level = $arg['unit_level'];
            $approval->name = $arg['name'];
            $approval->approval_module_id = $arg['approval_module_id'];
            $approval->department_id = $arg['department_id'];
            $approval->team_id = $arg['team_id'];
            $approval->odoo_job_id = $arg['odoo_job_id'];
            $approval->save();

            foreach ($approvalUsers as $approvalUser) {
                $approvalUser->approval_id = $approval->id;
                $approvalUser->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data saved successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            /**
             * @var Approval $approval
             */
            $approval = Approval::query()
                ->where('id', '=', $id)
                ->first();
            if (!$approval) {
                return response()->json([
                    'status' => false,
                    'message' => 'Approval not found.',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var ApprovalUser[] $approvalUsers
             */
            $approvalUsers = [];

            foreach ($request->input('approvers', []) as $approver) {
                $unitExis = Unit::query()
                    ->where('relation_id', '=', $approver['unit_relation_id'])
                    ->where('unit_level', '=', $approver['unit_level'])
                    ->exists();
                if (!$unitExis) {
                    return response()->json([
                        'status' => false,
                        'message' => 'One of unit is not exist.',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                $employeeExist = Employee::query()
                    ->where('id', '=', $approver['employee_id'])
                    ->exists();
                if (!$employeeExist) {
                    return response()->json([
                        'status' => false,
                        'message' => 'One of employee is not exist.',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                $approvalUser = new ApprovalUser();
                $approvalUser->employee_id = $approver['employee_id'];
                $approvalUser->unit_relation_id = $approver['unit_relation_id'];
                $approvalUser->unit_level = $approver['unit_level'];
                $approvalUser->department_id = $approver['department_id'];
                $approvalUser->team_id = $approver['team_id'];
                $approvalUser->odoo_job_id = $approver['odoo_job_id'];

                $approvalUsers[] = $approvalUser;
            }

            DB::beginTransaction();

            $approval->name = $request->input('name');
            $approval->save();

            ApprovalUser::query()
                ->where('approval_id', '=', $approval->id)
                ->delete();

            foreach ($approvalUsers as $approvalUser) {
                $approvalUser->approval_id = $approval->id;
                $approvalUser->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data updated successfully.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $approval = Approval::with(['approvalModule', 'team', 'department', 'job', 'approvalUsers'])
                ->orderBy('id', 'desc')
                ->find($id);

            if (!$approval) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data not found.',
                    'data' => null,
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully.',
                'data' => $approval,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data failed to retrieve.',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        /**
         * @var Approval $dataExists
         */
        $dataExists = Approval::where('id', $id)->first();
        if (!$dataExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found.',
                'data' => null,
            ], 500);
        }

        try {
            DB::beginTransaction();

            ApprovalUser::query()
                ->where('approval_id', '=', $id)
                ->delete();

            $dataExists->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data deleted successfully.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Data failed to delete.',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOrg($id) {
        $getUnit = Unit::where('id', $id)->get();
        $flattenedUnits = UnitHelper::flattenUnits($getUnit);

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully.',
            'data' => $flattenedUnits,
        ]);
    }

    /**
     * @param Employee $employee
     * @param string $approvalType
     *
     * @return ApprovalUser[]|Collection
     */
    public function getApprovalUser(Employee $employee, string $approvalType) {
        /**
         * @var ApprovalUser[] $result
         */
        $result = [];

        $lastUnit = $employee->getLastUnit();

        if ($lastUnit && $employee->department_id && $employee->team_id && $employee->job_id) {
            $result = ApprovalUser::query()->select(['approval_users.*'])
                ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
                ->join('approval_modules', 'approval_modules.id', '=', 'approvals.approval_module_id')
                ->where('approval_modules.name', '=', $approvalType)
                ->where('approvals.unit_relation_id', '=', $lastUnit->relation_id)
                ->where('approvals.unit_level', '=', $lastUnit->unit_level)
                ->where('approvals.department_id', '=', $employee->department->id)
                ->where('approvals.team_id', '=', $employee->team_id)
                ->where('approvals.odoo_job_id', '=', $employee->job_id)
                ->get();

            if (count($result) > 0) {
                return $result;
            }
        }

        if ($lastUnit && $employee->department_id && $employee->team_id) {
            $result = ApprovalUser::query()->select(['approval_users.*'])
                ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
                ->join('approval_modules', 'approval_modules.id', '=', 'approvals.approval_module_id')
                ->where('approval_modules.name', '=', $approvalType)
                ->where('approvals.unit_relation_id', '=', $lastUnit->relation_id)
                ->where('approvals.unit_level', '=', $lastUnit->unit_level)
                ->where('approvals.department_id', '=', $employee->department->id)
                ->where('approvals.team_id', '=', $employee->team_id)
                ->whereNull('approvals.odoo_job_id')
                ->get();

            if (count($result) > 0) {
                return $result;
            }
        }

        if ($lastUnit && $employee->department_id) {
            $result = ApprovalUser::query()->select(['approval_users.*'])
                ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
                ->join('approval_modules', 'approval_modules.id', '=', 'approvals.approval_module_id')
                ->where('approval_modules.name', '=', $approvalType)
                ->where('approvals.unit_relation_id', '=', $lastUnit->relation_id)
                ->where('approvals.unit_level', '=', $lastUnit->unit_level)
                ->where('approvals.department_id', '=', $employee->department->id)
                ->whereNull('approvals.team_id')
                ->whereNull('approvals.odoo_job_id')
                ->get();

            if (count($result) > 0) {
                return $result;
            }
        }

        if ($lastUnit && $employee->job_id) {
            $result = ApprovalUser::query()->select(['approval_users.*'])
                ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
                ->join('approval_modules', 'approval_modules.id', '=', 'approvals.approval_module_id')
                ->where('approval_modules.name', '=', $approvalType)
                ->where('approvals.unit_relation_id', '=', $lastUnit->relation_id)
                ->where('approvals.unit_level', '=', $lastUnit->unit_level)
                ->whereNull('approvals.department_id')
                ->whereNull('approvals.team_id')
                ->where('approvals.odoo_job_id', '=', $employee->job_id)
                ->get();

            if (count($result) > 0) {
                return $result;
            }
        }

        if ($lastUnit) {
            $result = ApprovalUser::query()->select(['approval_users.*'])
                ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
                ->join('approval_modules', 'approval_modules.id', '=', 'approvals.approval_module_id')
                ->where('approval_modules.name', '=', $approvalType)
                ->where('approvals.unit_relation_id', '=', $lastUnit->relation_id)
                ->where('approvals.unit_level', '=', $lastUnit->unit_level)
                ->whereNull('approvals.department_id')
                ->whereNull('approvals.team_id')
                ->whereNull('approvals.odoo_job_id')
                ->get();

            if (count($result) > 0) {
                return $result;
            }
        }

        if ($lastUnit) {
            /**
             * @var UnitHasJob $unitJob
             */
            $unitJob = UnitHasJob::query()
                ->where('unit_relation_id', '=', $lastUnit->relation_id)
                ->where('odoo_job_id', '=', $employee->job_id)
                ->first();

            if (!$unitJob) {
                return $result;
            }
            if (!$unitJob->parent) {
                return $result;
            }

            /**
             * @var Employee $employee
             */
            $employee = Employee::query()
                ->where('unit_id', '=', $unitJob->parent->unit_relation_id)
                ->where('job_id', '=', $unitJob->parent->odoo_job_id)
                ->first();
            if ($employee) {
                $approvalUser = new ApprovalUser();
                $approvalUser->employee_id = $employee->id;

                $result[] = $approvalUser;
            }
        }

        return $result;
    }
}
