<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\Job;
use App\Models\Unit;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobService extends BaseService
{
    public function index($id): JsonResponse
    {
        $jobs = Job::select(
            'jobs.name',
            'jobs.id',
            'u.relation_id',
            'u.id as units',
            'uj.unit_id',
            'uj.is_camera',
            'uj.is_upload',
            'uj.is_reporting',
            'uj.is_mandatory_reporting'
        )
            ->leftJoin('employees as emp', 'jobs.odoo_job_id', '=', 'emp.job_id')
            ->leftJoin('units as u', 'u.relation_id', '=', 'emp.corporate_id')
            ->leftJoin('unit_jobs as uj', 'jobs.id', '=', 'uj.job_id')
            ->whereNotNull('jobs.name')
            ->where('u.id', $id)
            ->groupBy(
                'jobs.name',
                'jobs.id',
                'u.relation_id',
                'u.id',
                'uj.is_camera',
                'uj.unit_id',
                'uj.is_upload',
                'uj.is_reporting',
                'uj.is_mandatory_reporting'
            )
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $jobs
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
}
