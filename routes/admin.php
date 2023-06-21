<?php

use App\Http\Controllers\API\ApprovalController;
use App\Http\Controllers\API\ApprovalModuleController;
use App\Http\Controllers\API\CabangController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\CorporateController;
use App\Http\Controllers\API\EmployeeAttendanceController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\EmployeeDetailController;
use App\Http\Controllers\API\EmployeeTimesheetController;
use App\Http\Controllers\API\EventCategoryController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\EventRecurringController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WorkLocationController;
use Illuminate\Support\Facades\Route;


//Route::group(['prefix' => 'admin', 'middleware' => 'auth:sanctum'], function () {
Route::group(['prefix' => 'admin'], function () {

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
        Route::post('{id}/avatar', [UserController::class, 'updateAvatar']);
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
        Route::delete('delete', [PermissionController::class, 'destroy']);
    });
    // End Permission

    // Begin Employee
    Route::group(['prefix' => 'employee'], function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('view/{id}', [EmployeeController::class, 'view']);
        Route::get('sync-to-users', [EmployeeController::class, 'syncToUser']);
    });
    // End Employee

    // Begin Company
    Route::group(['prefix' => 'company'], function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::get('view', [CompanyController::class, 'show']);
    });
    // End Company

    // Begin Corporate
    Route::group(['prefix' => 'corporate'], function () {
        Route::get('/', [CorporateController::class, 'index']);
        Route::get('view', [CorporateController::class, 'show']);
    });
    // End Corporate

    // Begin Cabang
    Route::group(['prefix' => 'cabang'], function () {
        Route::get('/', [CabangController::class, 'index']);
        Route::get('view', [CabangController::class, 'show']);
    });
    // End Cabang

    // Begin Setting
    Route::group(['prefix' => 'setting'], function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::post('save', [SettingController::class, 'save']);
        Route::put('update/{id}', [SettingController::class, 'update']);
        Route::put('bulk-update', [SettingController::class, 'bulkUpdate']);
        Route::delete('delete/{id}', [SettingController::class, 'delete']);
    });
    // End Setting

    // Begin employee timesheet
    Route::group(['prefix' => 'employee-timesheet'], function() {
        Route::get('/', [EmployeeTimesheetController::class, 'index']);
        Route::get('view/{id}', [EmployeeTimesheetController::class, 'view']);
        Route::post('create', [EmployeeTimesheetController::class, 'save']);
        Route::put('update/{id}', [EmployeeTimesheetController::class, 'edit']);
        Route::delete('delete/{id}', [EmployeeTimesheetController::class, 'delete']);
    });
    // End employee timesheet

    // Begin employee detail
    Route::group(['prefix' => 'employee-detail'], function() {
//        Route::get('/', [EmployeeTimesheetController::class, 'index']);
//        Route::get('view/{id}', [EmployeeTimesheetController::class, 'view']);
        Route::post('create', [EmployeeDetailController::class, 'create']);
//        Route::put('update/{id}', [EmployeeTimesheetController::class, 'edit']);
//        Route::delete('delete/{id}', [EmployeeTimesheetController::class, 'delete']);
    });
    // End employee detail

    // Begin employee timesheet
    Route::group(['prefix' => 'work-locations'], function() {
        Route::get('/', [WorkLocationController::class, 'index']);
        Route::get('view/{id}', [WorkLocationController::class, 'view']);
        Route::post('create', [WorkLocationController::class, 'save']);
        Route::put('update/{id}', [WorkLocationController::class, 'update']);
        Route::delete('delete/{id}', [WorkLocationController::class, 'delete']);
    });
    // End employee timesheet

    // Begin employee attendance
    Route::group(['prefix' => 'attendance'], function() {
        Route::get('/', [EmployeeAttendanceController::class, 'index']);
        Route::post('check-in/{id}', [EmployeeAttendanceController::class, 'checkIn']);
        Route::post('check-out/{id}', [EmployeeAttendanceController::class, 'checkOut']);
    });
    // End employee attendance


    // Begin Approval Module
    Route::group(['prefix' => 'approval-module'], function() {
        Route::get('', [ApprovalModuleController::class, 'index']);
        Route::post('create', [ApprovalModuleController::class, 'save']);
        Route::get('view/{id}', [ApprovalModuleController::class, 'show']);
        Route::put('update/{id}', [ApprovalModuleController::class, 'update']);
        Route::delete('delete/{id}', [ApprovalModuleController::class, 'delete']);
    });
    // End Approval Module

    // Begin Approval
    Route::group(['prefix' => 'approval'], function() {
        Route::get('', [ApprovalController::class, 'index']);
        Route::post('create', [ApprovalController::class, 'save']);
        Route::get('view/{id}', [ApprovalController::class, 'show']);
        Route::put('update/{id}', [ApprovalController::class, 'update']);
        Route::delete('delete/{id}', [ApprovalController::class, 'delete']);
    });
    // End Approval
});
