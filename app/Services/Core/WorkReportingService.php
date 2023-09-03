<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\Role;
use App\Models\Unit;
use App\Models\UnitReporting;
use App\Models\User;
use App\Models\WorkReporting;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WorkReportingService extends BaseService
{
    function getLastUnit($data) {
        $bottomData = null;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nestedData = $this->getLastUnit($value);
                if ($nestedData !== null) {
                    $bottomData = $nestedData;
                }
            } elseif ($key === 'value' && $value !== null) {
                $bottomData = $data;
            }
        }

        return $bottomData;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $unitRelationID = $request->get('unit_relation_id');
            $workReporting = WorkReporting::with('employee');
            $workReporting->join('employees', 'employees.id', '=', 'work_reportings.employee_id');
            $workReporting->select(['work_reportings.*']);

            if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

            } elseif ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
                $workReporting->leftJoin('user_operating_units', 'user_operating_units.unit_relation_id', '=', 'employees.default_operating_unit_id');
                $workReporting->where(function (Builder $builder) use ($user) {
                    $builder->orWhere('user_operating_units.user_id', '=', $user->id);
                });
            } else if ($this->isRequestedRoleLevel(Role::RoleStaff)) {
                $subQuery = "(
                            WITH RECURSIVE job_data AS (
                                SELECT * FROM unit_has_jobs
                                WHERE unit_relation_id = '{$user->employee->unit_id}' AND odoo_job_id = {$user->employee->job_id}
                                UNION ALL
                                SELECT uj.* FROM unit_has_jobs uj
                                INNER JOIN job_data jd ON jd.id = uj.parent_unit_job_id
                            )
                            SELECT * FROM job_data
                        ) relatedJob";
                $workReporting->join(DB::raw($subQuery), function (JoinClause $joinClause) {
                    $joinClause->on(DB::raw("relatedJob.odoo_job_id"), '=', DB::raw('employees.job_id'))
                        ->where(DB::raw("relatedJob.unit_relation_id"), '=', DB::raw('employees.unit_id'));
                });

                $workReporting->where(function (Builder $builder) use ($user) {
                    $builder->orWhere(function(Builder $builder) use ($user) {
                        $builder->where('employees.job_id', '=', $user->employee->job_id)
                            ->where('employees.unit_id', '=', $user->employee->unit_id)
                            ->where('employees.id', '=', $user->employee_id);
                    })->orWhere(function (Builder $builder) use ($user) {
                        $builder->orWhere('employees.job_id', '!=', $user->employee->job_id)
                            ->orWhere('employees.unit_id', '!=', $user->employee->unit_id);
                    });
                });
            }

            if ($unitRelationID) {
                $workReporting->where(function (Builder $builder) use ($unitRelationID) {
                    $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                        $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                            ->orWhere('employees.cabang_id', '=', $unitRelationID)
                            ->orWhere('employees.area_id', '=', $unitRelationID)
                            ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                            ->orWhere('employees.corporate_id', '=', $unitRelationID);
                    });
                });
            }

            $workReporting->groupBy('work_reportings.id');
            $workReporting->orderBy('work_reportings.id', 'DESC');

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetch work reporting data',
                'data' => $this->list($workReporting, $request)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function save($request): JsonResponse
    {
        $empData = auth()->user()->employee;

        DB::beginTransaction();
        try {
            $workReporting = new WorkReporting();
            $workReporting->title = $request->title;
            $workReporting->date = Carbon::now();
            $workReporting->job_type = $request->job_type;
            $workReporting->job_description = $request->job_description;
            $workReporting->image = $request->image;
            $workReporting->employee_id = auth()->user()->employee_id;
            $workReporting->reference_type = $request->reference_type;
            $workReporting->reference_id = $request->reference_id;

            if (!$workReporting->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save work reporting data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully save work reporting data',
                'data' => $workReporting
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function view($id): JsonResponse
    {
        try {
            $workReporting = WorkReporting::find($id);

            if (!$workReporting) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work reporting data not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetch work reporting data',
                'data' => $workReporting
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update($request, $id): JsonResponse
    {
        $workReporting = WorkReporting::find($id);
        if (!$workReporting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work reporting data not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $workReporting->title = $request->title;
            $workReporting->date = Carbon::now();;
            $workReporting->job_type = $request->job_type;
            $workReporting->job_description = $request->job_description;
            $workReporting->image = $request->image;
            $workReporting->employee_id = auth()->user()->employee_id;

            if (!$workReporting->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update work reporting data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully update work reporting data',
                'data' => $workReporting
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        $workReporting = WorkReporting::find($id);
        if (!$workReporting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work reporting data not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            if (!$workReporting->delete()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete work reporting data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully delete work reporting data'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createMandatoryWorkReporting($request) {
        DB::beginTransaction();
        try {
            $create = new UnitReporting();
            $create->unit_jobs_id = $request->unit_jobs_id;
            $create->type = $request->type;
            $create->total_reporting = $request->total_reporting;
            $create->reporting_names = json_encode($request->reporting_names);

            if (!$create->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch mandatory work reporting data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully create mandatory work reporting data',
                'data' => $create
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch mandatory work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
