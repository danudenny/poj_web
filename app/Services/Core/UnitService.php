<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UnitService extends BaseService
{
    /**
     * @param $data
     * @return JsonResponse
     * @throws Exception
     */
    public function index($data): JsonResponse
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
                    'child.id as child_id',
                    'child.name as child_name',
                    'child.unit_level as child_unit_level',
                    'child.parent_unit_id as child_parent_unit_id',
                )
                ->where('parent.unit_level', $parentLevel)
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

    public function paginatedListUnit(Request $request): JsonResponse
    {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $query = Unit::query();

            if ($user->isHighestRole(Role::RoleAdmin)) {
                /**
                 * @var Builder $query
                 */
                $query = Unit::query()->from('unit_data')
                    ->withRecursiveExpression('unit_data', Unit::query()->where('relation_id', '=', $user->employee->getLastUnitID())->unionAll(
                            Unit::query()->select(['units.*'])
                                ->join('unit_data', 'units.parent_unit_id', '=', 'unit_data.relation_id')
                    ));
                $query->orderBy('unit_level', 'ASC');
            } else if ($user->isHighestRole(Role::RoleStaff)) {
                $query->where('relation_id', '=', $user->employee->getLastUnitID());
            }

            $query->when($request->filled('name'), function(Builder $builder) use ($request) {
                $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(request()->query('name')).'%']);
            });

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

    public function getRelatedUnit(): JsonResponse
    {
        $roles = auth()->user()->roles;
        $datas = [];
        foreach ($roles as $role) {
            if ($role->role_level === 'superadmin') {
                $datas = Unit::all();
            } else {
                $empUnit = auth()->user()->employee->getRelatedUnit();
                $datas = UnitHelper::flattenUnits($empUnit);
            }
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Success Fetch Data',
            'data' => $datas
        ]);
    }
}
