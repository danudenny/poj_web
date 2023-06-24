<?php

namespace App\Services\Core;

use App\Models\ApprovalModule;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ApprovalModuleService extends BaseService
{
    public function index($request): JsonResponse
    {
        try {
            $approvalModules = ApprovalModule::query();
            $approvalModules->with('approvals');
            $approvalModules->when(request()->has('name'), function ($query) {
                $query->where('name', 'like', '%' . request()->get('name') . '%');
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully.',
                'data' => $approvalModules->paginate(request()->get('limit') ?? 10),
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
        $dataExists = ApprovalModule::where('name', $request->name)->first();
        if ($dataExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data already exists.',
                'data' => null,
            ], 500);
        }

        DB::beginTransaction();
        try {
            $approvalModule = ApprovalModule::create([
                'name' => $request->name,
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data saved successfully.',
                'data' => $approvalModule,
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
        $dataExists = ApprovalModule::where('id', $id)->first();
        if (!$dataExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found.',
                'data' => null,
            ], 500);
        }

        $duplicateName = ApprovalModule::where('name', $request->name)->where('id', '!=', $id)->first();
        if ($duplicateName) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data already exists.',
                'data' => null,
            ], 500);
        }

        DB::beginTransaction();
        try {
            $approvalModule = ApprovalModule::where('id', $id)->update([
                'name' => $request->name,
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully.',
                'data' => $approvalModule,
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
            $approvalModule = ApprovalModule::where('id', $id)->first();
            if (!$approvalModule) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data not found.',
                    'data' => null,
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully.',
                'data' => $approvalModule,
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
        $dataExists = ApprovalModule::find($id);
        if (!$dataExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found.',
                'data' => null,
            ], 500);
        }

        DB::beginTransaction();
        try {
            $approvalModule = ApprovalModule::where('id', $id)->delete();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data deleted successfully.',
                'data' => $approvalModule,
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

}
