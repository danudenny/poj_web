<?php

namespace App\Services\Core;

use App\Models\Job;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
}
