<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\EventCategoryController;
use App\Http\Controllers\API\EventRecurringController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\PermissionController;


Route::group(['prefix' => 'admin', 'middleware' => 'auth:sanctum'], function () {
    // Begin User
    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('view', [UserController::class, 'view']);
        Route::get('/roles', [UserController::class, 'getRoles']);
        Route::post('save', [UserController::class, 'save']);
        Route::post('update', [UserController::class, 'update']);
        Route::delete('delete', [UserController::class, 'delete']);
        Route::post('restore', [UserController::class, 'restore']);
        Route::delete('destroy', [UserController::class, 'destroy']);
    });
    // End User

    // Begin Role
    Route::group(['prefix' => 'role'], function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('view', [RoleController::class, 'view']);
        Route::get('permissions', [RoleController::class, 'getPermissions']);
        Route::post('save', [RoleController::class, 'save']);
        Route::post('update', [RoleController::class, 'update']);
        Route::post('toggle-status', [RoleController::class, 'toggleRoleStatus']);
        Route::delete('delete', [RoleController::class, 'delete']);
        Route::post('restore', [RoleController::class, 'restore']);
        Route::delete('destroy', [RoleController::class, 'destroy']);
    });
    // End Role

    // Begin Permission
    Route::group(['prefix' => 'permission'], function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::get('view', [PermissionController::class, 'view']);
        Route::post('save', [PermissionController::class, 'save']);
        Route::post('update', [PermissionController::class, 'update']);
        Route::delete('destroy', [PermissionController::class, 'destroy']);
    });
    // End Permission

    // Begin Employee
    Route::group(['prefix' => 'employee'], function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('view', [EmployeeController::class, 'view']);
    });
    // End Employee
});
