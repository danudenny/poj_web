<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    public const SUCCESS_FETCH = 'Successfully Fetching';
    public const SUCCESS_CREATED = 'Successfully Created';
    public const SUCCESS_UPDATED = 'Successfully Updated';
    public const SUCCESS_DELETED = 'Successfully Deleted';
    public const SUCCESS_RESTORE = 'Successfully Restore';
    public const SUCCESS_DESTROYED = 'Successfully Destroyed';
    public const LOGIN_SUCCESS = 'Login Successfully';

    /**
     * return success response.
     *
     * @param $result
     * @param $message
     * @param int $code
     * @return JsonResponse
     */
    public function sendSuccess($result, $message, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, $code);
    }

    /**
     * return error response.
     *
     * @param $error
     * @param array $errorMessages
     * @param int $code
     * @return JsonResponse
     */
    public function sendError($error, array $errorMessages = [], int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

}
