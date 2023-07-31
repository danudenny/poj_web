<?php

namespace App\Services\Core;

use App\Models\MasterLeave;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MasterLeaveService extends BaseService
{
    public function index($request): JsonResponse
    {
        $masterLeave = MasterLeave::query();
        $masterLeave->when($request->leave_name, function ($q) use ($request) {
            $q->whereRaw("LOWER(leave_name) LIKE '%" . strtolower($request->leave_name) . "%'");
        });
        $masterLeave->when($request->leave_type, function ($q) use ($request) {
            $q->where('leave_type', $request->leave_type);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully get master leave data',
            'data' => $masterLeave->paginate($request->per_page ?? 10)
        ], 200);
    }

    public function show($id): JsonResponse
    {
        $masterLeave = MasterLeave::find($id);
        if (!$masterLeave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Master leave not found',
                'data' => null
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully get master leave data',
            'data' => $masterLeave
        ], 200);
    }

    public function save($request): JsonResponse
    {
        $checkMasterLeave = MasterLeave::whereRaw("LOWER(leave_name) LIKE '%" . strtolower($request->leave_name) . "%'")->first();
        if ($checkMasterLeave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave name already exist',
                'data' => null
            ], 400);
        }

        $checkMasterLeave = MasterLeave::whereRaw("LOWER(leave_code) LIKE '%" . strtolower($request->leave_code) . "%'")->first();
        if ($checkMasterLeave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave code already exist',
                'data' => null
            ], 400);
        }
        DB::beginTransaction();
        try {
            $master = new MasterLeave();
            $master->leave_name = $request->leave_name;
            $master->leave_code = $request->leave_code;
            $master->leave_type = $request->leave_type;

            if (!$master->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save master leave',
                    'data' => null
                ], 400);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully save master leave',
                'data' => $master
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    public function update($request, $id): JsonResponse
    {
        $checkMasterLeave = MasterLeave::find($id);
        if (!$checkMasterLeave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Master leave not found',
                'data' => null
            ], 400);
        }

        $checkMasterLeave = MasterLeave::whereRaw("LOWER(leave_name) LIKE '%" . strtolower($request->leave_name) . "%'")->where('id', '!=', $id)->first();
        if ($checkMasterLeave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave name already exist',
                'data' => null
            ], 400);
        }

        $checkMasterLeave = MasterLeave::whereRaw("LOWER(leave_code) LIKE '%" . strtolower($request->leave_code) . "%'")->where('id', '!=', $id)->first();
        if ($checkMasterLeave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave code already exist',
                'data' => null
            ], 400);
        }

        DB::beginTransaction();
        try {
            $checkMasterLeave->leave_name = $request->leave_name;
            $checkMasterLeave->leave_code = $request->leave_code;
            $checkMasterLeave->leave_type = $request->leave_type;

            if (!$checkMasterLeave->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update master leave',
                    'data' => null
                ], 400);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully update master leave',
                'data' => $checkMasterLeave
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    public function delete($id): JsonResponse
    {
        $checkMasterLeave = MasterLeave::find($id);
        if (!$checkMasterLeave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Master leave not found',
                'data' => null
            ], 400);
        }

        DB::beginTransaction();
        try {
            if (!$checkMasterLeave->delete()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete master leave',
                    'data' => null
                ], 400);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully delete master leave',
                'data' => $checkMasterLeave
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
}
