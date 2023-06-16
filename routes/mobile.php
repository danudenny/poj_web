<?php

use App\Http\Controllers\API\EmployeeAttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Mobile\AuthController;
use App\Http\Controllers\API\Mobile\UserController;

Route::group(['prefix' => 'mobile'], function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::group(['prefix' => 'profile'], function () {
            Route::get('/', [UserController::class, 'profile']);
            Route::post('/update', [UserController::class, 'update']);
        });
    });
});
