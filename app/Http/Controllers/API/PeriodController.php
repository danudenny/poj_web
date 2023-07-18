<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Core\PeriodService;
use Illuminate\Http\JsonResponse;

class PeriodController extends Controller
{
    private PeriodService $periodService;

    public function __construct(PeriodService $periodService) {
        $this->periodService = $periodService;
    }

    public function index(): JsonResponse
    {
        return $this->periodService->index();
    }
}
