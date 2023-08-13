<?php

namespace App\Services\Core;

use App\Models\Department;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DepartmentService
{

    public function getAll($request): JsonResponse
    {
        $department = Department::query();
        $department->with(['teams', 'employee.unit']);
        $department->when('name', function ($query) use ($request) {
            $query->whereRaw('LOWER(name) LIKE ?', '%' . strtolower($request->name) . '%');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch data',
            'data' => $department->get()
        ]);
    }

    public function index($request): JsonResponse
    {
        $department = Department::with('teams')
            ->leftJoin('employees as emp', 'departments.odoo_department_id', '=', 'emp.department_id')
            ->leftJoin('units as u', 'u.relation_id', '=', 'emp.unit_id')
            ->whereNotNull('emp.department_id')
            ->where('emp.unit_id', '<>', 0)
            ->select('departments.name as department_name', 'u.name as unit_name', 'departments.id', 'emp.unit_id');
        $department->when($request->name, function ($query) use ($request) {
            $query->where('departments.name', 'like', '%' . $request->name . '%');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch data',
            'data' => $department->get()
        ]);
    }

    public function show($id, $unit_id): JsonResponse
    {
        $department = Department::with('teams')
            ->leftJoin('employees as emp', 'departments.odoo_department_id', '=', 'emp.department_id')
            ->leftJoin('units as u', 'u.relation_id', '=', 'emp.unit_id')
            ->whereNotNull('emp.department_id')
            ->where('emp.unit_id', '<>', 0)
            ->select('departments.name as department_name', 'u.name as unit_name', 'departments.id', 'emp.unit_id')
            ->where('departments.id', $id)
            ->where('emp.unit_id', $unit_id)
            ->first();

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
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign company'
            ], 500);
        }
    }

    public function assignTeam($request, $id, $unit_id): JsonResponse
    {
        $department = Department::where('departments.id', $id)
            ->whereHas('employee', function ($query) use ($unit_id) {
                $query->where('unit_id', $unit_id);
            })
            ->first();
        if (!$department) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $department->teams()->whereNotIn('id', $request->teams)->detach();

            foreach ($request->teams as $teamId) {
                DB::table('department_has_teams')->updateOrInsert(
                    [
                        'department_id' => $id,
                        'team_id' => $teamId
                    ],
                    [
                        'unit_id' => $unit_id
                    ]
                );
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success fetch data',
                'data' => $department
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
