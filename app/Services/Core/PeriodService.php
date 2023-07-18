<?php

namespace App\Services\Core;

use App\Models\Period;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PeriodService extends BaseService {
    
    public function index(): JsonResponse
    {
        try {
            $currentYear = Carbon::now()->year;
            $periods = Period::where('year', $currentYear)->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Data fetched successfully',
                'data' => $periods
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch data',
                'data' => $e->getMessage(),
            ], 500);
        }
    }
}
