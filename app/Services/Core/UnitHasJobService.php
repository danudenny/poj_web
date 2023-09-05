<?php

namespace App\Services\Core;

use App\Http\Requests\CreateUnitJobRequest;
use App\Http\Requests\Job\AssignParentJobRequest;
use App\Models\Job;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Unit;
use App\Models\UnitHasJob;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class UnitHasJobService extends BaseService
{
    public function index(Request $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $query = UnitHasJob::query()->with(['parent']);
            $query->select(['unit_has_jobs.*']);
            $query->orderBy('unit_has_jobs.id', 'ASC');

            $unitRelationIDTopBottom = $request->get('unit_relation_id_top_bottom');
            $odooJobIDTopBottom = $request->get('odoo_job_id_top_bottom');

            if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

            } else {
                if (!$unitRelationIDTopBottom && !$odooJobIDTopBottom) {
                    $unitRelationIDTopBottom = $user->employee->unit_id;
                    $odooJobIDTopBottom = $user->employee->job_id;
                }
            }

            if ($odooJobIDTopBottom && $unitRelationIDTopBottom) {
                $subQuery = UnitHasJob::query()->from('job_data', 'unit_has_jobs')
                    ->withRecursiveExpression('job_data', UnitHasJob::query()
                        ->whereRaw("unit_relation_id = '$unitRelationIDTopBottom'")
                        ->whereRaw("odoo_job_id = '$odooJobIDTopBottom'")
                        ->unionAll(
                            UnitHasJob::query()->select(['unit_has_jobs.*'])
                                ->join('job_data', 'job_data.id', '=', 'unit_has_jobs.parent_unit_job_id')
                        )
                    );
                $jobs = DB::table(DB::raw("({$subQuery->toSql()}) as d"))->select('*')->mergeBindings($subQuery->getQuery());

                $query->orderBy('units.unit_level', 'ASC');
            }

            $query->join('units', 'units.relation_id', '=', 'unit_has_jobs.unit_relation_id');

            if ($unit_relation_id = $request->query('unit_relation_id')) {
                $query->where('unit_relation_id', '=', $unit_relation_id);
            }

            if ($unitName = $request->query('unit_name')) {
                $query->whereRaw('units.name ILIKE ?', ["%" . $unitName . "%"]);
            }

            if ($jobName = $request->query('job_name')) {
                $query->join('jobs', 'jobs.odoo_job_id', '=', 'unit_has_jobs.odoo_job_id');
                $query->whereRaw('jobs.name ILIKE ?', ["%" . $jobName . "%"]);
            }

            if ($parentUnitName = $request->query('parent_unit_name', '')) {
                $query->join('unit_has_jobs AS parent', 'parent.id', '=', 'unit_has_jobs.parent_unit_job_id');
                $query->join('units AS parent_unit', 'parent_unit.relation_id', '=', 'parent.unit_relation_id');
                $query->whereRaw('parent_unit.name ILIKE ?', ["%" . $parentUnitName . "%"]);
            }

            if ($jobName = $request->query('parent_job_name')) {
                if ($parentUnitName == '') {
                    $query->join('unit_has_jobs AS parent', 'parent.id', '=', 'unit_has_jobs.parent_unit_job_id');
                }

                $query->join('jobs AS parent_job', 'parent_job.odoo_job_id', '=', 'parent.odoo_job_id');
                $query->whereRaw('parent_job.name ILIKE ?', ["%" . $jobName . "%"]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => $this->list($query, $request)
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listJob(Request $request) {
        $user = $request->user();

        $query = Job::query();
    }

    public function assignParent(AssignParentJobRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

//            if (!$user->hasPermissionName(Permission::AssignParentJob)) {
//                return response()->json([
//                    'status' => false,
//                    'message' => "You don't have access",
//                ], ResponseAlias::HTTP_FORBIDDEN);
//            }

            /**
             * @var UnitHasJob $unitJob
             */
            $unitJob = UnitHasJob::query()
                ->where('id', '=', $request->input('unit_has_job_id'))
                ->first();
            if (!$unitJob) {
                return response()->json([
                    'status' => false,
                    'message' => "Unit job not found!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var UnitHasJob $parentUnitJob
             */
            $parentUnitJob = UnitHasJob::query()
                ->where('id', '=', $request->input('parent_unit_has_job_id'))
                ->first();
            if (!$parentUnitJob) {
                return response()->json([
                    'status' => false,
                    'message' => "Parent unit job not found!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($parentUnitJob->parent_unit_job_id == $unitJob->id) {
                return response()->json([
                    'status' => false,
                    'message' => "Parent Job is cycling!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $unitJob->parent_unit_job_id = $parentUnitJob->id;
            $unitJob->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createUnitJob(CreateUnitJobRequest $request) {
        try {
            $unitHasJob = UnitHasJob::query()
                ->where('unit_relation_id', '=', $request->input('unit_relation_id'))
                ->where('odoo_job_id', '=', $request->input('odoo_job_id'))
                ->exists();
            if ($unitHasJob) {
                return response()->json([
                    'status' => true,
                    'message' => 'Success',
                ]);
            }

            DB::beginTransaction();

            $unitJob = new UnitHasJob();
            $unitJob->unit_relation_id = $request->input('unit_relation_id');
            $unitJob->odoo_job_id = $request->input('odoo_job_id');
            $unitJob->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function chartView(): JsonResponse
    {
        $relationId = request()->query('relation_id');
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => UnitHasJob::getHierarchicalData(intval($relationId))
        ]);
    }
}
