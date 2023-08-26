<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
use App\Models\Employee;
use App\Models\EmployeeTimesheet;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class UnitService extends BaseService
{
    /**
     * @param $data
     * @return JsonResponse
     * @throws Exception
     */
    public function index($data): JsonResponse
    {
        $auth = auth()->user();
        try {
            $roleLevel = $data->header('X-Selected-Role');
            $parentLevel = intval($data->unit_level);
            $childLevel = $parentLevel + 1;
            $units = [];

            if ($roleLevel === Role::RoleSuperAdministrator) {
                $units = DB::table('units as parent')
                    ->leftJoin('units as child', function ($join) use ($parentLevel, $childLevel) {
                        $join->on('parent.relation_id', '=', 'child.parent_unit_id')
                            ->where('child.unit_level', $childLevel);
                    })->select(
                        'parent.id as parent_id',
                        'parent.relation_id as parent_relation_id',
                        'parent.name as parent_name',
                        'parent.unit_level as parent_unit_level',
                        'parent.parent_unit_id as parent_parent_unit_id',
                        'child.id as child_id',
                        'child.name as child_name',
                        'child.unit_level as child_unit_level',
                        'child.parent_unit_id as child_parent_unit_id'
                    )
                    ->where('parent.unit_level', $parentLevel)
                    ->when($data->name, function ($query) use ($data) {
                        $query->whereRaw('LOWER(parent.name) LIKE ?', ['%'.strtolower($data->name).'%']);
                    })
                    ->orderBy('parent.id')
                    ->orderBy('child.id')
                    ->get();

            }
            else if ($roleLevel === Role::RoleAdmin) {
                $empUnit = $auth->employee->getRelatedUnit();
                $lastUnit = $auth->employee->getLastUnit();
                $empUnit[] = $lastUnit;
                $relationIds = [];

                if ($requestRelationID = $this->getRequestedUnitID()) {
                    $relationIds[] = $requestRelationID;
                } else {
                    $flatUnit = UnitHelper::flattenUnits($empUnit);
                    $relationIds = array_column($flatUnit, 'relation_id');
                }

                $units =  DB::table('units as parent')
                    ->leftJoin('units as child', function ($join) use ($parentLevel, $childLevel) {
                        $join->on('parent.relation_id', '=', 'child.parent_unit_id')
                            ->where('child.unit_level', $childLevel);
                    })
                    ->select(
                        'parent.id as parent_id',
                        'parent.relation_id as parent_relation_id',
                        'parent.name as parent_name',
                        'parent.unit_level as parent_unit_level',
                        'parent.parent_unit_id as parent_parent_unit_id',
                        'child.id as child_id',
                        'child.name as child_name',
                        'child.unit_level as child_unit_level',
                        'child.parent_unit_id as child_parent_unit_id',
                    )
                    ->where('parent.unit_level', $parentLevel)
                    ->whereIn('parent.parent_unit_id', $relationIds)
                    ->orWhereIn('child.parent_unit_id', $relationIds)
                    ->orderBy('parent.id')
                    ->orderBy('child.id')
                    ->get();
            }

            $nestedUnits = [];

            foreach ($units as $unit) {
                $childUnit = [
                    'id' => $unit->child_id,
                    'name' => $unit->child_name,
                    'unit_level' => $unit->child_unit_level,
                    'parent_unit_id' => $unit->child_parent_unit_id,
                ];

                if (!isset($nestedUnits[$unit->parent_id])) {
                    $parentUnit = [
                        'id' => $unit->parent_id,
                        'parent_relation_id' => $unit->parent_relation_id,
                        'name' => $unit->parent_name,
                        'unit_level' => $unit->parent_unit_level,
                        'parent_unit_id' => $unit->parent_parent_unit_id,
                        'child' => [],
                    ];

                    $nestedUnits[$unit->parent_id] = $parentUnit;
                }

                if (!is_null($unit->child_id) && !is_null($unit->child_name)) {
                    $nestedUnits[$unit->parent_id]['child'][] = $childUnit;
                }
            }

            $nestedUnits = array_values($nestedUnits);

            return response()->json([
                'status' => 'success',
                'message' => 'Success Fetch Data',
                'data' => $nestedUnits
            ]);


        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @throws Exception
     */
    public function paginatedListUnit(Request $request): JsonResponse
    {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $query = Unit::query();
            $query->select(['units.*']);

            $unitRelationIDTopButtom = $request->get('unit_relation_id_top_buttom');

            if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

            } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
                if (!$unitRelationIDTopButtom) {
                    $defaultUnitRelationID = $user->employee->unit_id;

                    if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                        $defaultUnitRelationID = $requestUnitRelationID;
                    }

                    $unitRelationIDTopButtom = $defaultUnitRelationID;
                }
            } else if ($this->isRequestedRoleLevel(Role::RoleStaffApproval)) {
                $query->where('units.relation_id', '=', $user->employee->unit_id);
            } else {
                $query->where('units.relation_id', '=', $user->employee->unit_id);
            }

            if ($unitRelationIDTopButtom) {
                /**
                 * @var Builder $query
                 */
                $query = Unit::query()->from('unit_data')
                    ->withRecursiveExpression('unit_data', Unit::query()->where('relation_id', '=', $unitRelationIDTopButtom)->unionAll(
                        Unit::query()->select(['units.*'])
                            ->join('unit_data', function (JoinClause $query) {
                                $query->on('units.parent_unit_id', '=', 'unit_data.relation_id')
                                    ->whereRaw('units.unit_level = unit_data.unit_level + 1');
                            })
                    ));
            }

            if ($allUnitStructured = $request->query('unit_relation_id_structured')) {
                $query = Unit::query()->fromRaw("(SELECT * FROM parent_data UNION SELECT * FROM child_data) d")
                    ->withRecursiveExpression('child_data', Unit::query()->where('relation_id', '=', $allUnitStructured)->unionAll(
                        Unit::query()->select(['units.*'])
                            ->join('child_data', function (JoinClause $query) {
                                $query->on('units.parent_unit_id', '=', 'child_data.relation_id');
                            })
                    ))->withRecursiveExpression('parent_data', Unit::query()->where('relation_id', '=', $allUnitStructured)->unionAll(
                        Unit::query()->select(['units.*'])
                            ->join('parent_data', function (JoinClause $query) {
                                $query->on('units.relation_id', '=', 'parent_data.parent_unit_id');
                            })
                    ));
            }

            $query->when($request->filled('name'), function(Builder $builder) use ($request) {
                $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(request()->query('name')).'%']);
            });

            if ($unitLevel = $request->input('unit_level')) {
                $query->whereIn('unit_level', explode(',', $unitLevel));
            }

            $query->orderBy('unit_level', 'ASC');

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => $this->list($query, $request)
            ]);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @param $data
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function view($data, $id): JsonResponse
    {
        try {
            $parentLevel = intval($data->unit_level);
            $childLevel = $parentLevel + 1;

            $units = DB::table('units as parent')
                ->leftJoin('units as child', function ($join) use ($parentLevel, $childLevel) {
                    $join->on('parent.relation_id', '=', 'child.parent_unit_id')
                        ->where('child.unit_level', $childLevel);
                })
                ->select(
                    'parent.id as parent_id',
                    'parent.name as parent_name',
                    'parent.unit_level as parent_unit_level',
                    'parent.parent_unit_id as parent_parent_unit_id',
                    'parent.lat',
                    'parent.long',
                    'parent.radius',
                    'parent.early_buffer',
                    'parent.late_buffer',
                    'child.id as child_id',
                    'child.name as child_name',
                    'child.unit_level as child_unit_level',
                    'child.parent_unit_id as child_parent_unit_id',
                )
                ->where('parent.unit_level', $parentLevel)
                ->where('parent.id', $id)
                ->orderBy('parent.id')
                ->orderBy('child.id')
                ->get();

            $nestedUnits = [];

            foreach ($units as $unit) {
                $childUnit = [
                    'id' => $unit->child_id,
                    'name' => $unit->child_name,
                    'unit_level' => $unit->child_unit_level,
                    'parent_unit_id' => $unit->child_parent_unit_id,
                ];

                if (!isset($nestedUnits[$unit->parent_id])) {
                    $parentUnit = [
                        'id' => $unit->parent_id,
                        'name' => $unit->parent_name,
                        'unit_level' => $unit->parent_unit_level,
                        'parent_unit_id' => $unit->parent_parent_unit_id,
                        'lat' => $unit->lat,
                        'long' => $unit->long,
                        'radius' => $unit->radius,
                        'early_buffer' => $unit->early_buffer,
                        'late_buffer' => $unit->late_buffer,
                        'child' => []
                    ];

                    $nestedUnits[$unit->parent_id] = $parentUnit;
                }

                if (!is_null($unit->child_id) && !is_null($unit->child_name)) {
                    $nestedUnits[$unit->parent_id]['child'][] = $childUnit;
                }
            }

            $nestedUnits = array_values($nestedUnits);

            return response()->json([
                'status' => 'success',
                'message' => 'Success Fetch Data',
                'data' => $nestedUnits
            ]);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    public function update($data, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $units = Unit::where('id', $id)->first();
            $units->lat = $data->lat;
            $units->long = $data->long;
            $units->radius = $data->radius;
            $units->early_buffer = $data->early_buffer;
            $units->late_buffer = $data->late_buffer;

            if (!$units->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update unit'
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success update unit'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getRelatedUnit($request): JsonResponse
    {
        $role = auth()->user()->getHighestRole();
        $datas = [];

        if ($role->role_level === 'superadmin') {
            $datas = Unit::where('unit_level', $request->unit_level)
                ->get();
        } else {
            $empUnit = auth()->user()->employee->getRelatedUnit();
            $lastUnit = auth()->user()->employee->getLastUnit();
            $empUnit[] = $lastUnit;
            $datas = UnitHelper::flattenUnits($empUnit);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Success Fetch Data',
            'data' => $datas
        ]);
    }

    public function detailUnit(Request $request, int $id) {
        try {
            $unit = Unit::query()
                ->where('id', '=', $id)
                ->first();
            if (!$unit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit not found'
                ], ResponseAlias::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Success Fetch Data',
                'data' => $unit
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
