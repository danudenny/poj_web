<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\Job;
use App\Models\JobHasUnit;
use App\Models\Unit;
use App\Models\UnitHasJob;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class JobService extends BaseService
{
    public function index($id): JsonResponse
    {
        $subquery = DB::table('jobs as j')
            ->select('j.id', 'j.name', 'u.id AS unitID', 'u.relation_id')
            ->join('employees as e', 'j.odoo_job_id', '=', 'e.job_id')
            ->join('units as u', function ($join) {
                $join->on('u.relation_id', '=', DB::raw('CAST(e.corporate_id AS BIGINT)'))
                    ->orWhere('u.relation_id', '=', DB::raw('CAST(e.kanwil_id AS BIGINT)'))
                    ->orWhere('u.relation_id', '=', DB::raw('CAST(e.area_id AS BIGINT)'))
                    ->orWhere('u.relation_id', '=', DB::raw('CAST(e.cabang_id AS BIGINT)'))
                    ->orWhere('u.relation_id', '=', DB::raw('CAST(e.outlet_id AS BIGINT)'));
            })
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
        $jobs->with(['roles', 'units']);
        $jobs->when($request->input('name'), function ($query, $name) {
            $query->whereRaw("LOWER(name) ILIKE '%" . strtolower($name) . "%'");
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $jobs->paginate($request->input('per_page', 10))
        ]);
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
                $query->wherePivot('is_mandatory_reporting', true);
            }
        }])->find($id);

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

                $unit = Unit::find($unitId);

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

        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to detach job from unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateMandatoryReporting($request, $id): JsonResponse
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
            $type = $request->input('type');
            $totalReporting = $request->input('total_reporting');
            $reportingNames = $request->input('reporting_names');

            $syncData = [];
            foreach ($jobIds as $jobId) {
                $syncData[$jobId] = [
                    'type' => $type,
                    'total_reporting' => $totalReporting,
                    'reporting_names' => json_encode($reportingNames)
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
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to attach jobs to units',
                'error' => $e->getMessage()
            ], 500);
        }

    }
}
