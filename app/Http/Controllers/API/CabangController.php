<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Models\Cabang;
use App\Services\Core\CabangService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class CabangController extends BaseController
{
    private CabangService $cabangSvc;

    public function __construct(CabangService $cabangSvc) {
        $this->cabangSvc = $cabangSvc;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->cabangSvc->index($request);
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
            $result = $this->cabangSvc->view($data);

            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (Exception | InvalidArgumentException $error) {

            return $this->sendError($error->getMessage(), [], 500);
        }
    }
}
