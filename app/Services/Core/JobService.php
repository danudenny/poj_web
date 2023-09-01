<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\Job;
use App\Models\JobHasUnit;
use App\Models\Role;
use App\Models\Unit;
use App\Models\UnitHasJob;
use App\Models\User;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JobService extends BaseService
{
    public function index($request, $id): JsonResponse
    {
        $subquery = DB::table('jobs as j')
            ->select('j.id', 'j.name', 'u.id AS unitID', 'u.relation_id')
            ->join('employees as e', 'j.odoo_job_id', '=', 'e.job_id')
            ->join('units as u', 'u.relation_id', '=', 'e.unit_id')
            ->when($id, function ($query, $id) {
                $query->where('u.id', $id);
            })
            ->groupBy('j.id', 'u.id');

        $jobs = DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $jobs
        ]);
    }

    public function allJobs($request): JsonResponse
    {
        $jobs = Job::query();
        $jobs->with(['roles', 'unitJob', 'units']);
        $jobs->when($request->input('name'), function ($query, $name) {
            $query->whereRaw("LOWER(name) ILIKE '%" . strtolower($name) . "%'");
        });
        $jobs->when($request->input('unit_id'), function ($query, $unitId) {
            $query->whereHas('unitJob', function ($subquery) use ($unitId) {
                $subquery->where('unit_relation_id', '=', $unitId);
            });
        });
        $jobs = $jobs->get();

        $data = [];

        foreach ($jobs as $job) {
            if ($request->input('flat', false)) {
                foreach ($job->unitJob as $unit) {
                    if (!$request->input('unit_id') || $unit->relation_id == $request->input('unit_id')) {
                        $data[] = [
                            'job_id' => $job->id,
                            'unit_id' => $unit->relation_id,
                            'job_name' => $job->name,
                            'roles' => $job->roles,
                            'unit_name' => $unit->name,
                            'is_camera' => $unit->pivot->is_camera,
                            'is_upload' => $unit->pivot->is_upload,
                            'is_mandatory_reporting' => $unit->pivot->is_mandatory_reporting,
                        ];
                    }
                }
            } else {
                if (!$request->input('unit_id')) {
                    $data[] = [
                        'job_id' => $job->id,
                        'job_name' => $job->name,
                        'roles' => $job->roles,
                        'units' => $job->unitJob,
                    ];
                }
            }
        }

        $perPage = $request->input('per_page', 10);
        $currentPage = $request->input('page', 1);

        $totalDataCount = count($data);
        $totalPages = ceil($totalDataCount / $perPage);

        $paginatedData = array_slice($data, ($currentPage - 1) * $perPage, $perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => [
                'current_page' => $currentPage,
                'data' => $paginatedData,
                'per_page' => $perPage,
                'total' => count($data),
                'last_page' => $totalPages,
            ]
        ]);

    }

    public function paginate($items, $perPage = 5, $page = null, $options = []): LengthAwarePaginator
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function view($id): JsonResponse
    {
        $job = Job::with(['roles', 'units'])->find($id);
        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $job
        ]);
    }

    public function getById($request, $id): JsonResponse
    {
        $isMandatoryReporting = $request->is_mandatory_reporting;
        $unit = Unit::with(['jobs' => function ($query) use ($isMandatoryReporting) {
            if ($isMandatoryReporting) {
                $query->where('is_mandatory_reporting', true);
            }
        }])
            ->where('relation_id', '=', $id)
            ->first();


        if (!$unit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit not found'
            ], 404);
        }

        $unit->jobs->map(function ($job) use ($unit) {
            $job->is_camera = $job->pivot->is_camera;
            $job->is_upload = $job->pivot->is_upload;
            $job->is_reporting = $job->pivot->is_reporting;
            $job->is_mandatory_reporting = $job->pivot->is_mandatory_reporting;
            $job->reporting_type = $job->pivot->type;
            $job->total_reporting = $job->pivot->total_reporting;
            $job->total_normal = $job->pivot->total_normal;
            $job->total_backup = $job->pivot->total_backup;
            $job->total_overtime = $job->pivot->total_overtime;
            $job->reporting_names = $job->pivot->reporting_names;
            unset($job->pivot);
            return $job;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $unit
        ]);
    }

    public function store($request, $unitId): JsonResponse
    {
        $units = $request->input('units');

        DB::beginTransaction();

        try {
            foreach ($units as $unitData) {
                $jobIds = $unitData['job_ids'];
                $isCamera = $unitData['is_camera'] ?? false;
                $isUpload = $unitData['is_upload'] ?? false;
                $isReporting = $unitData['is_reporting'] ?? false;
                $isReportingMandatory = $unitData['is_mandatory_reporting'] ?? false;

                $unit = Unit::where('relation_id', (string) $unitId)->first();

                if (!$unit) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unit not found for ID: ' . $unitId
                    ], 404);
                }

                foreach ($jobIds as $jobId) {
                    $existingRecord = $unit->jobs()->where('job_id', $jobId)->exists();

                    if ($existingRecord) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Job already associated with the unit',
                            'unit_id' => $unitId,
                            'job_id' => $jobId
                        ], 400);
                    }
                }

                $unit->jobs()->attach($jobIds, [
                    'is_camera' => $isCamera,
                    'is_upload' => $isUpload,
                    'is_reporting' => $isReporting,
                    'is_mandatory_reporting' => $isReportingMandatory,
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Jobs attached to units successfully'
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to attach jobs to units',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignRoles($request, $id) {
        $job = Job::find($id);
        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $job->id = $id;
            $job->save();
            if (!empty($request->roles)) {
                if (!$job->roles()->sync($request->roles)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to sync'
                    ], 400);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully synced to roles',
                'data' => $request->roles
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update($request, $id): JsonResponse
    {
        $unit = Unit::find($id);
        if (!$unit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $jobIds = $request->input('job_ids');

            $isCamera = $request->input('is_camera', false);
            $isUpload = $request->input('is_upload', false);
            $isReporting = $request->input('is_reporting', false);
            $is_reporting_mandatory = $request->input('is_reporting_mandatory', false);

            $unit->jobs()->sync($jobIds, [
                'is_camera' => $isCamera,
                'is_upload' => $isUpload,
                'is_reporting' => $isReporting,
                'is_reporting_mandatory' => $is_reporting_mandatory,
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Jobs updated to unit successfully'
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update jobs to unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($unitId, $jobId): JsonResponse
    {
        $unit = Unit::find($unitId);
        if (!$unit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $unit->jobs()->detach($jobId);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Job detached from unit successfully'
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to detach job from unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateMandatoryReporting(Request $request, int $id): JsonResponse
    {
        try {
            /**
             * @var Unit $unit
             */
            $unit = Unit::query()->where('id', '=', $id)->first();
            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found'
                ], 404);
            }

            $arg = [
                'job_ids' => $request->input('job_ids', []),
                'type' => $request->input('type'),
                'total_reporting' => $request->input('total_reporting'),
                'reporting_names' => $request->input('reporting_names'),
                'total_normal' => $request->input('total_normal'),
                'total_overtime' => $request->input('total_overtime'),
                'total_backup' => $request->input('total_backup')
            ];

            DB::beginTransaction();

            $syncData = [];
            foreach ($arg['job_ids'] as $jobId) {
                $syncData[$jobId] = [
                    'type' => $arg['type'],
                    'total_reporting' => $arg['total_reporting'],
                    'reporting_names' => json_encode($arg['reporting_names']),
                    'total_normal' => $arg['total_normal'],
                    'total_backup' => $arg['total_backup'],
                    'total_overtime' => $arg['total_overtime']
                ];
            }

            $unit->jobs()->syncWithoutDetaching($syncData);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Jobs updated to unit successfully',
                'data' => $unit->jobs()->get()
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update jobs to unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function pivotInsert(): JsonResponse
    {
        DB::beginTransaction();
        try {
            $emp = Employee::leftJoin('units AS u', 'u.relation_id', '=', 'employees.unit_id')
                ->leftJoin('jobs AS j', 'employees.job_id', '=', 'j.odoo_job_id')
                ->select('j.odoo_job_id as job_id', 'u.relation_id as unit_id')
                ->whereNotNull('j.id')
                ->whereNotNull('u.relation_id')
                ->distinct()
                ->orderBy('job_id')
                ->chunk(1000, function ($data) {
                    foreach ($data as $item) {
                        UnitHasJob::updateOrInsert(
                            [
                                'odoo_job_id' => $item->job_id,
                                'unit_relation_id' => $item->unit_id,
                            ],
                            []
                        );
                    }
                });

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Jobs attached to units successfully',
                'data' => $emp
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to attach jobs to units',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    public function structuredJob(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $unitRelationIDTopBottom = $request->get('unit_relation_id_top_bottom');
        $odooJobIDTopBottom = $request->get('odoo_job_id_top_bottom');

        $query = Job::query();
        $query->select(['jobs.*']);
        $query->groupBy('jobs.id');

        if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

        } else {
            if (!$unitRelationIDTopBottom && !$odooJobIDTopBottom) {
                $unitRelationIDTopBottom = $user->employee->unit_id;
                $odooJobIDTopBottom = $user->employee->job_id;
            }
        }

        if ($odooJobIDTopBottom && $unitRelationIDTopBottom) {
            $subQuery = "(
                            WITH RECURSIVE job_data AS (
                                SELECT * FROM unit_has_jobs
                                WHERE unit_relation_id = '$unitRelationIDTopBottom' AND odoo_job_id = $odooJobIDTopBottom
                                UNION ALL
                                SELECT uj.* FROM unit_has_jobs uj
                                INNER JOIN job_data jd ON jd.id = uj.parent_unit_job_id
                            )
                            SELECT * FROM job_data
                        ) relatedJob";
            $query->join(DB::raw($subQuery), function (JoinClause $joinClause) {
                $joinClause->on(DB::raw("relatedJob.odoo_job_id"), '=', DB::raw('jobs.odoo_job_id'));
            });
        }

        if ($name = $request->get('name')) {
            $query->where('jobs.name', 'ILIKE', "%$name%");
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($query, $request)
        ]);
    }

    public function deleteAssignedJob($id) {
        $job = Job::with(['units'])->find($id);

        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $job->units()->detach();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Job detached from unit successfully'
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to detach job from unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
