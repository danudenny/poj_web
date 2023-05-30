<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
     * @return \Illuminate\Http\Response
     */
    public function sendSuccess($result, $message, $code = 200)
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
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
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
