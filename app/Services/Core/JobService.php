<?php

namespace App\Services\Core;

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
    public function index(Request $request) {
        $job = Job::query();
        $job->when($request->filled('name'), function(Builder $builder) use ($request) {
            $builder->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($request->query('name')).'%']);
        });
        $job->orderBy('name', 'ASC');

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $this->list($job, $request)
        ]);
    }

    public function store($request, $id): JsonResponse
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

            $unit->jobs()->attach($jobIds, [
                'is_camera' => $isCamera,
                'is_upload' => $isUpload,
                'is_reporting' => $isReporting,
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Jobs attached to unit successfully'
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to attach jobs to unit',
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
