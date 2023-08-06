<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Core\KantorPerwakilanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KantorPerwakilanController extends Controller
{
    private KantorPerwakilanService $kantorPerwakilanService;

    public function __construct(KantorPerwakilanService $kantorPerwakilanService)
    {
        $this->kantorPerwakilanService = $kantorPerwakilanService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->kantorPerwakilanService->index($request);
    }
}
