<?php

namespace App\Services\Core;

use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DepartmentService
{
    public function index($request): JsonResponse
    {
        $department = Department::query();
        $department->with('unit');
        $department->when('name', function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->name . '%');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch data',
            'data' => $department->get()
        ]);
    }

    public function show($id): JsonResponse
    {
        $department = Department::with('unit')->find($id);
        if (!$department) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch data',
            'data' => $department
        ]);
    }

    public function assignCompany($id): JsonResponse
    {
        $department = Department::find($id);
        if (!$department) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $department->company_id = $id;
            $department->save();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success fetch data',
                'data' => $department
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign company'
            ], 500);
        }
    }
}
