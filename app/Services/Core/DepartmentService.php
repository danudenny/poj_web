<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
use App\Models\Department;
use App\Models\Role;
use App\Models\Unit;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DepartmentService extends BaseService
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
        $roleLevel = $request->header('X-Selected-Role');
        $user = auth()->user();
       $departmentsWithUnitsAndTeams = Department::select([
            'departments.name AS department_name',
            'u.name AS unit_name',
            'u.unit_level AS unit_level',
            'departments.id',
            'emp.unit_id',
            'teams.id AS team_id',
            'teams.name AS team_name',
            'department_has_teams.unit_id AS team_unit_id'
        ])
            ->leftJoinSub(function ($join) use ($user, $roleLevel, $request) {
                $join->select('unit_id', 'department_id')
                    ->from('employees')
                    ->when($roleLevel === Role::RoleSuperAdministrator, function ($query) use ($request) {
                        $query->where('unit_id', '<>', 0);
                    })
                    ->when($roleLevel === Role::RoleAdmin, function ($query) use ($user, $request) {
                        $empUnit = $user->employee->getRelatedUnit();
                        $relationIds = [];

                        if ($requestRelationID = request()->header('X-Unit-Relation-ID')) {
                            $relationIds[] = $requestRelationID;
                        } else {
                            $flatUnit = UnitHelper::flattenUnits($empUnit);
                            $relationIds = array_column($flatUnit, 'relation_id');
                        }

                        $query->whereIn('unit_id', $relationIds);
                    })
                    ->whereNotNull('department_id')
                    ->whereNotNull('unit_id')
                    ->groupBy('unit_id', 'department_id');
            }, 'emp', function ($join) {
                $join->on('departments.odoo_department_id', '=', 'emp.department_id');
            })
            ->leftJoin('units AS u', 'u.relation_id', '=', 'emp.unit_id')
            ->leftJoin('department_has_teams', function ($join) {
                $join->on('departments.id', '=', 'department_has_teams.department_id')
                    ->on('emp.unit_id', '=', 'department_has_teams.unit_id');
            })
            ->leftJoin('teams', 'department_has_teams.team_id', '=', 'teams.id')
           ->get();

        $processedData = [];

        foreach ($departmentsWithUnitsAndTeams as $row) {
            if ($row->unit_id === null) {
                continue;
            }
            $departmentId = $row->id;
            $unitId = $row->unit_id;

            if (!isset($processedData[$departmentId])) {
                $processedData[$departmentId] = [
                    'department_name' => $row->department_name,
                    'id' => $row->id,
                    'units' => []
                ];
            }

            if (!isset($processedData[$departmentId]['units'][$unitId])) {
                $processedData[$departmentId]['units'][$unitId] = [
                    'unit_name' => $row->unit_name,
                    'unit_id' => $row->unit_id,
                    'unit_level' => $row->unit_level,
                    'teams' => []
                ];
            }

            if (!empty($row->team_name)) {
                $teamAlreadyAdded = false;
                foreach ($processedData[$departmentId]['units'][$unitId]['teams'] as $addedTeam) {
                    if ($addedTeam['id'] == $row->team_id && $addedTeam['team_unit_id'] == $row->team_unit_id) {
                        $teamAlreadyAdded = true;
                        break;
                    }
                }

                if (!$teamAlreadyAdded) {
                    $processedData[$departmentId]['units'][$unitId]['teams'][] = [
                        'id' => $row->team_id,
                        'name' => $row->team_name,
                        'team_unit_id' => $row->team_unit_id
                    ];
                }
            }
        }

        $result = [];

        foreach ($processedData as $departmentData) {
            foreach ($departmentData['units'] as $unitData) {
                $result[] = [
                    'department_name' => $departmentData['department_name'],
                    'unit_name' => $unitData['unit_name'],
                    'id' => $departmentData['id'],
                    'unit_id' => $unitData['unit_id'],
                    'unit_level' => $unitData['unit_level'],
                    'teams' => $unitData['teams']
                ];
            }
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch data',
            'data' => $result
        ]);

    }

    public function show($id, $unit_id): JsonResponse
    {
        $departmentsWithUnitsAndTeams = Department::select([
            'departments.name AS department_name',
            'u.name AS unit_name',
            'departments.id',
            'emp.unit_id',
            'teams.id AS team_id',
            'teams.name AS team_name',
            'department_has_teams.unit_id AS team_unit_id'
        ])
            ->leftJoinSub(function ($join) {
                $join->select('unit_id', 'department_id')
                    ->from('employees')
                    ->whereNotNull('department_id')
                    ->where('unit_id', '<>', 0)
                    ->groupBy('unit_id', 'department_id');
            }, 'emp', function ($join) {
                $join->on('departments.odoo_department_id', '=', 'emp.department_id');
            })
            ->leftJoin('units AS u', 'u.relation_id', '=', 'emp.unit_id')
            ->leftJoin('department_has_teams', function ($join) {
                $join->on('departments.id', '=', 'department_has_teams.department_id')
                    ->on('emp.unit_id', '=', 'department_has_teams.unit_id');
            })
            ->leftJoin('teams', 'department_has_teams.team_id', '=', 'teams.id')
            ->where('departments.id', $id)
            ->where('emp.unit_id', $unit_id)
            ->get();

        $processedData = [];

        foreach ($departmentsWithUnitsAndTeams as $row) {
            $departmentId = $row->id;
            $unitId = $row->unit_id;

            if (!isset($processedData[$departmentId])) {
                $processedData[$departmentId] = [
                    'department_name' => $row->department_name,
                    'id' => $row->id,
                    'units' => []
                ];
            }

            if (!isset($processedData[$departmentId]['units'][$unitId])) {
                $processedData[$departmentId]['units'][$unitId] = [
                    'unit_name' => $row->unit_name,
                    'unit_id' => $row->unit_id,
                    'teams' => []
                ];
            }

            if (!empty($row->team_name)) {
                $processedData[$departmentId]['units'][$unitId]['teams'][] = [
                    'id' => $row->team_id,
                    'name' => $row->team_name,
                    'team_unit_id' => $row->team_unit_id
                ];
            }
        }

        $result = [];

        foreach ($processedData as $departmentData) {
            foreach ($departmentData['units'] as $unitData) {
                $result[] = [
                    'department_name' => $departmentData['department_name'],
                    'unit_name' => $unitData['unit_name'],
                    'id' => $departmentData['id'],
                    'unit_id' => $unitData['unit_id'],
                    'teams' => $unitData['teams']
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch data',
            'data' => $result[0]
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
        $department = Department::where('id', $id)->first();
        $unit = Unit::where('relation_id', $unit_id)->first();

        if (!$department) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $department->units()->detach($unit->relation_id);

            foreach ($request->teams as $teamId) {
                $department->units()->attach($unit->relation_id, ['team_id' => $teamId]);
                $department->units()->updateExistingPivot($unit->relation_id, ['unit_level' => $unit->unit_level]);
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
