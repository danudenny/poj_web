<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('login_with_auth_key', [AuthController::class, 'login_with_auth_key']);
    Route::post('forget_password', [AuthController::class, 'forget_password']);
    Route::get('has_token', [AuthController::class, 'has_token']);
    Route::post('reset_password', [AuthController::class, 'reset_password']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('permission', [AuthController::class, 'permission']);
    });
});
