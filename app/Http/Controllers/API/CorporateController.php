<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Models\Corporate;
use App\Services\Core\CorporateService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CorporateController extends BaseController
{
    private CorporateService $corpSvc;

    public function __construct(CorporateService $corpSvc) {
        $this->corpSvc = $corpSvc;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->corpSvc->index($request);
            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (Exception | InvalidArgumentException $error) {
            return $this->sendError($error->getMessage());
        }

    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $data = $request->only('id');
            $result = $this->corpSvc->view($data);

            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (Exception | InvalidArgumentException $error) {

            return $this->sendError($error->getMessage(), [], 500);
        }
    }
}
