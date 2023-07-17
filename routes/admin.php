<?php

use App\Http\Controllers\API\ApprovalController;
use App\Http\Controllers\API\ApprovalModuleController;
use App\Http\Controllers\API\CabangController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\CorporateController;
use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\EmployeeAttendanceController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\EmployeeDetailController;
use App\Http\Controllers\API\EmployeeTimesheetController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\IncidentController;
use App\Http\Controllers\API\JobController;
use App\Http\Controllers\API\OvertimeController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WorkLocationController;
use App\Http\Controllers\API\WorkReportingController;
use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\KanwilController;
use App\Http\Controllers\API\OutletController;
use App\Http\Controllers\API\UnitController;


Route::group(['prefix' => 'admin', 'middleware' => 'auth:sanctum'], function () {
//Route::group(['prefix' => 'admin'], function () {

    // Begin User
    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('view', [UserController::class, 'view']);
        Route::get('profile', [UserController::class, 'profile']);
        Route::get('/roles', [UserController::class, 'getRoles']);
        Route::post('save', [UserController::class, 'save']);
        Route::post('update', [UserController::class, 'update']);
        Route::post('toggle-status', [UserController::class, 'toggleRoleStatus']);
        Route::delete('delete', [UserController::class, 'delete']);
        Route::post('restore', [UserController::class, 'restore']);
        Route::delete('destroy', [UserController::class, 'destroy']);
        Route::post('{id}/avatar', [UserController::class, 'updateAvatar']);
        Route::put('update-token/{id}', [UserController::class, 'updateToken']);
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
        Route::post('assign-schedule', [EmployeeTimesheetController::class, 'assignSchedule']);
        Route::get('get-schedule', [EmployeeTimesheetController::class, 'getEmployeeSchedule']);
        Route::get('view-schedule', [EmployeeTimesheetController::class, 'showEmployeeSchedule']);
        Route::get('show-schedule', [EmployeeTimesheetController::class, 'showEmployeeScheduleById']);
        Route::put('update-schedule', [EmployeeTimesheetController::class, 'updateEmployeeSchedule']);
        Route::delete('delete-schedule', [EmployeeTimesheetController::class, 'deleteEmployeeSchedule']);
        Route::get('periods', [EmployeeTimesheetController::class, 'getPeriods']);
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
        Route::get('view', [WorkLocationController::class, 'show']);
        Route::post('create', [WorkLocationController::class, 'save']);
        Route::put('update', [WorkLocationController::class, 'update']);
        Route::delete('delete/{id}', [WorkLocationController::class, 'delete']);
    });
    // End employee timesheet

    // Begin employee attendance
    Route::group(['prefix' => 'attendance'], function() {
        Route::get('/', [EmployeeAttendanceController::class, 'index']);
        Route::get('view/{id}', [EmployeeAttendanceController::class, 'view']);
        Route::post('check-in', [EmployeeAttendanceController::class, 'checkIn']);
        Route::post('check-out', [EmployeeAttendanceController::class, 'checkOut']);
    });
    // End employee attendance

    // Begin Kanwil
    Route::group(['prefix' => 'kanwil'], function () {
        Route::get('/', [KanwilController::class, 'index']);
        Route::get('view', [KanwilController::class, 'view']);
    });
    // End Kanwil

    // Begin Outlet
    Route::group(['prefix' => 'outlet'], function () {
        Route::get('/', [OutletController::class, 'index']);
        Route::get('view', [OutletController::class, 'view']);
    });
    // End Outlet

    // Begin Unit
    Route::group(['prefix' => 'unit'], function () {
        Route::get('/', [UnitController::class, 'index']);
        Route::get('related-unit', [UnitController::class, 'relatedUnit']);
        Route::get('view/{id}', [UnitController::class, 'view']);
        Route::get('all', [UnitController::class, 'allUnitNoFilter']);
        Route::get('paginated', [UnitController::class, 'paginatedListUnits']);
        Route::put('update/{id}', [UnitController::class, 'update']);

    });
    // End Unit

    // Begin Approval Module
    Route::group(['prefix' => 'approval-module'], function() {
        Route::get('', [ApprovalModuleController::class, 'index']);
        Route::get('view/{id}', [ApprovalModuleController::class, 'show']);
        Route::post('create', [ApprovalModuleController::class, 'save']);
        Route::put('update/{id}', [ApprovalModuleController::class, 'update']);
        Route::delete('delete/{id}', [ApprovalModuleController::class, 'delete']);
    });
    // End Approval Module

    // Begin Approval
    Route::group(['prefix' => 'approval'], function() {
        Route::get('', [ApprovalController::class, 'index']);
        Route::get('get-unit', [ApprovalController::class, 'getOrg']);
        Route::post('create', [ApprovalController::class, 'save']);
        Route::get('view/{id}', [ApprovalController::class, 'show']);
        Route::put('update/{id}', [ApprovalController::class, 'update']);
        Route::delete('delete/{id}', [ApprovalController::class, 'delete']);
    });
    // End Approval

    // Begin Department
    Route::group(['prefix' => 'department'], function() {
        Route::get('', [DepartmentController::class, 'index']);
        Route::put('create/{id}', [DepartmentController::class, 'assign']);
        Route::get('view/{id}', [DepartmentController::class, 'show']);
    });
    // End Department

    // Begin Backups
    Route::group(['prefix' => 'backup'], function() {
        Route::get('', [BackupController::class, 'index']);
        Route::get('view/{id}', [BackupController::class, 'show']);
        Route::post('create', [BackupController::class, 'create']);
        Route::put('approve/{id}', [BackupController::class, 'approve']);
        Route::post('check-in/{id}', [BackupController::class, 'checkIn']);
    });
    // End Backups

    // Begin Incident
    Route::group(['prefix' => 'incident', 'middleware' => ['auth:sanctum']], functioN() {
        Route::get('', [IncidentController::class, 'index']);
        Route::get('view/{incidentID}', [IncidentController::class, 'view']);
        Route::post('create', [IncidentController::class, 'create']);
        Route::post('approval/{incidentID}', [IncidentController::class, 'approval']);
        Route::post('closure/{incidentID}', [IncidentController::class, 'closure']);
        Route::post('upload-image', [IncidentController::class, 'uploadImage']);
    });
    // End Incident

    // Begin Event
    Route::group(['prefix' => 'event', 'middleware' => ['auth:sanctum']], function() {
        Route::get('', [EventController::class, 'index']);
        Route::get('view/{id}', [EventController::class, 'view']);
        Route::get('employee-event', [EventController::class, 'employeeEvent']);
        Route::post('create', [EventController::class, 'create']);
        Route::post('/approve/{id}', [EventController::class, 'approve']);
    });
    // End Event

    // Begin Work Reporting
    Route::group(['prefix' => 'work-reporting', 'middleware' => ['auth:sanctum']], function() {
        Route::get('', [WorkReportingController::class, 'index']);
        Route::get('view/{id}', [WorkReportingController::class, 'show']);
        Route::post('create', [WorkReportingController::class, 'store']);
        Route::put('update/{id}', [WorkReportingController::class, 'edit']);
        Route::delete('delete/{id}', [WorkReportingController::class, 'delete']);
    });
    // End Work Reporting

    // Begin Job
    Route::group(['prefix' => 'job', 'middleware' => ['auth:sanctum']], function() {
        Route::get('', [JobController::class, 'index']);
        Route::post('save/{id}', [JobController::class, 'store']);
        Route::put('update/{id}', [JobController::class, 'update']);
        Route::delete('delete/{unit_id}/{job_id}', [JobController::class, 'delete']);
    });
    // End Job

    // Begin Overtime
    Route::group(['prefix' => 'overtime', 'middleware' => ['auth:sanctum']], function () {
        Route::get('/', [OvertimeController::class, 'index']);
        Route::get('/view/{id}', [OvertimeController::class, 'view']);
        Route::get('/employee-overtime', [OvertimeController::class, 'employee_overtimes']);
        Route::post('', [OvertimeController::class, 'create']);
        Route::post('approval/{id}', [OvertimeController::class, 'approval']);
        Route::post('check-in', [OvertimeController::class, 'checkIn']);
        Route::post('check-out', [OvertimeController::class, 'checkOut']);
    });
    // End Overtime
});
