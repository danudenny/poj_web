<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
use App\Models\Approval;
use App\Models\ApprovalModule;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ApprovalService extends BaseService
{
    public function index($request): JsonResponse
    {
        try {
            $approvals = Approval::query();
            $approvals->with(['approvalModule', 'users']);
            $approvals->when($request->name, function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->name . '%');
            });
            $approvals->when($request->approval_module_id, function($q) use ($request) {
                $q->where('approval_module_id', $request->approval_module_id);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully.',
                'data' => $approvals->paginate(request()->get('limit') ?? 10),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data failed to retrieve.',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function save($request): JsonResponse
    {
        $dataExists = Approval::where('name', $request->name)->first();
        if ($dataExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data already exists.',
                'data' => null,
            ], 500);
        }

        $approvalModuleExists = ApprovalModule::where('id', $request->approval_module_id)->first();
        if (!$approvalModuleExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval module not found.',
                'data' => null,
            ], 500);
        }

        if (count($request->user_id) > 0) {
            foreach ($request->user_id as $userId) {
                $userExists = User::where('id', $userId)->first();
                if (!$userExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User not found.',
                        'data' => null,
                    ], 500);
                }
            }
        }

        DB::beginTransaction();
        try {
            $approval = Approval::create([
                'approval_module_id' => $request->approval_module_id,
                'name' => $request->name,
                'is_active' => $request->is_active,
                'unit_level' => $request->unit_level,
            ]);
            $approval->users()->attach($request->user_id);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data saved successfully.',
                'data' => $approval,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Data failed to save.',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function update($request, $id): JsonResponse
    {
        $dataExists = Approval::where('id', $id)->first();
        if (!$dataExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found.',
                'data' => null,
            ], 500);
        }

        $approvalModuleExists = ApprovalModule::where('id', $request->approval_module_id)->first();
        if (!$approvalModuleExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval module not found.',
                'data' => null,
            ], 500);
        }

        $duplicateName = Approval::where('name', $request->name)->where('id', '!=', $id)->first();
        if ($duplicateName) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data already exists.',
                'data' => null,
            ], 500);
        }

        foreach ($request->user_id as $userId) {
            $userExists = User::where('id', $userId)->first();
            if (!$userExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found.',
                    'data' => null,
                ], 500);
            }
        }

        DB::beginTransaction();
        try {
            $approval = Approval::find($id);
            $approval->update([
                'approval_module_id' => $request->approval_module_id,
                'name' => $request->name,
                'is_active' => $request->is_active,
                'unit_level' => $request->unit_level,
            ]);

            $approval->users()->sync($request->user_id);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully.',
                'data' => $approval,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Data failed to update.',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $approval = Approval::with(['approvalModule', 'users.employee', 'users.employee.unit'])
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
        $dataExists = Approval::where('id', $id)->first();
        if (!$dataExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found.',
                'data' => null,
            ], 500);
        }

        DB::beginTransaction();
        try {
            $approval = Approval::find($id);
            $approval->delete();
            $approval->users()->detach();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data deleted successfully.',
                'data' => $approval,
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
}
