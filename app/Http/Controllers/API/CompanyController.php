<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Services\Core\CompanyService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CompanyController extends BaseController
{
    private CompanyService $companySvc;

    public function __construct(CompanyService $companySvc)
    {
        $this->companySvc = $companySvc;
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
            $result = $this->companySvc->index($request);
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
            $result = $this->companySvc->view($data);

            return $this->sendSuccess($result, self::SUCCESS_FETCH);

        } catch (Exception | InvalidArgumentException $error) {

            return $this->sendError($error->getMessage(), [], 500);
        }
    }

}
