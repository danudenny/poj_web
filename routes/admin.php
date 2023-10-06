<?php

use App\Http\Controllers\API\AdminUnitController;
use App\Http\Controllers\API\ApprovalController;
use App\Http\Controllers\API\ApprovalModuleController;
use App\Http\Controllers\API\AttendanceCorrectionController;
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
use App\Http\Controllers\API\KantorPerwakilanController;
use App\Http\Controllers\API\KanwilController;
use App\Http\Controllers\API\LeaveRequestController;
use App\Http\Controllers\API\MasterLeaveController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\OperatingUnitController;
use App\Http\Controllers\API\OutletController;
use App\Http\Controllers\API\OvertimeController;
use App\Http\Controllers\API\PeriodController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\PolicyController;
use App\Http\Controllers\API\PublicHolidayController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\TeamController;
use App\Http\Controllers\API\TimesheetReportController;
use App\Http\Controllers\API\UnitController;
use App\Http\Controllers\API\UnitJobController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WorkLocationController;
use App\Http\Controllers\API\WorkReportingController;
use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;


Route::get('admin/employee/sync-to-users', [EmployeeController::class, 'syncToUser']);
Route::group(['prefix' => 'admin/setting'], function () {
    Route::get('/', [SettingController::class, 'index']);
});
Route::group(['prefix' => 'admin/policy'], function () {
    Route::get('/', [PolicyController::class, 'get']);
    Route::post('/', [PolicyController::class, 'set'])->middleware('auth:sanctum');
});
Route::group(['prefix' => 'admin', 'middleware' => 'auth:sanctum, switch_role'], function () {
    // Begin User
    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('view', [UserController::class, 'view']);
        Route::get('profile', [UserController::class, 'profile']);
        Route::get('profile-v2', [UserController::class, 'profileV2']);
        Route::get('/roles', [UserController::class, 'getRoles']);
        Route::post('save', [UserController::class, 'save']);
        Route::post('update', [UserController::class, 'update']);
        Route::post('change-password', [UserController::class, 'changePassword']);
        Route::post('toggle-status', [UserController::class, 'toggleRoleStatus']);
        Route::delete('delete', [UserController::class, 'delete']);
        Route::post('restore', [UserController::class, 'restore']);
        Route::delete('destroy', [UserController::class, 'destroy']);
        Route::post('{id}/avatar', [UserController::class, 'updateAvatar']);
        Route::put('update-token/{id}', [UserController::class, 'updateToken']);
        Route::post('change-profile-picture', [UserController::class, 'changeProfile']);
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
//    Route::group(['prefix' => 'role'], function () {
//        Route::get('/', [\App\Http\Controllers\API\RolePermission\RoleController::class, 'index']);
//        Route::post('save', [\App\Http\Controllers\API\RolePermission\RoleController::class, 'save']);
//        Route::put('update/{id}', [\App\Http\Controllers\API\RolePermission\RoleController::class, 'update']);
//    });
    // End Role

    // Begin Permission
    Route::group(['prefix' => 'permission'], function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::get('view', [PermissionController::class, 'view']);
        Route::post('save', [PermissionController::class, 'save']);
        Route::post('update', [PermissionController::class, 'update']);
        Route::delete('delete', [PermissionController::class, 'destroy']);
    });
//    Route::group(['prefix' => 'permission'], function () {
//        Route::get('/', [\App\Http\Controllers\API\RolePermission\PermissionController::class, 'index']);
//        Route::post('save', [\App\Http\Controllers\API\RolePermission\PermissionController::class, 'save']);
//    });
    // End Permission

    // Begin Employee
    Route::group(['prefix' => 'employee'], function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('paginated', [EmployeeController::class, 'listPaginatedEmployee']);
        Route::get('view/{id}', [EmployeeController::class, 'view']);
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
        Route::post('save', [SettingController::class, 'save']);
        Route::put('update/{id}', [SettingController::class, 'update']);
        Route::put('bulk-update', [SettingController::class, 'bulkUpdate']);
        Route::delete('delete/{id}', [SettingController::class, 'delete']);
    });
    // End Setting

    // Begin employee timesheet
    Route::group(['prefix' => 'employee-timesheet'], function() {
        Route::get('/{id}', [EmployeeTimesheetController::class, 'index']);
        Route::get('view/{unit_id}/{id}', [EmployeeTimesheetController::class, 'view']);
        Route::post('create/{id}', [EmployeeTimesheetController::class, 'save']);
        Route::put('update/{id}', [EmployeeTimesheetController::class, 'edit']);
        Route::delete('delete/{id}', [EmployeeTimesheetController::class, 'delete']);
        Route::post('assign-schedule', [EmployeeTimesheetController::class, 'assignSchedule']);
        Route::post('reassign-schedule', [EmployeeTimesheetController::class, 'reAssignSchedule']);
        Route::put('update-schedule', [EmployeeTimesheetController::class, 'updateEmployeeSchedule']);
        Route::delete('delete-schedule', [EmployeeTimesheetController::class, 'deleteEmployeeSchedule']);
        Route::delete('delete-employee-timesheet/{id}', [EmployeeTimesheetController::class, 'deleteEmployeeTimesheet']);
        Route::get('periods', [EmployeeTimesheetController::class, 'getPeriods']);
        Route::get('view-employee-schedule/{id}', [EmployeeTimesheetController::class, 'viewEmployeeTimesheetSchedule']);
        Route::post('update-employee-schedule/{id}', [EmployeeTimesheetController::class, 'updateEmployeeTimesheetSchedule']);
    });
    Route::group(['prefix' => 'timesheet-schedule'], function() {
        Route::get('get-schedule', [EmployeeTimesheetController::class, 'getEmployeeSchedule']);
        Route::get('view-schedule', [EmployeeTimesheetController::class, 'showEmployeeSchedule']);
        Route::get('show-schedule', [EmployeeTimesheetController::class, 'scheduleById']);
        Route::get('schedules', [EmployeeTimesheetController::class, 'indexSchedule']);
        Route::post('sync-non-shift', [EmployeeTimesheetController::class, 'syncNonShiftSchedule']);
    });
    Route::group(['prefix' => 'periods'], function() {
        Route::get('', [PeriodController::class, 'index']);
    });
    // End employee timesheet

    // Begin employee detail
    Route::group(['prefix' => 'employee-detail'], function() {
        Route::post('create', [EmployeeDetailController::class, 'create']);
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
        Route::post('check-in-v2/{id}', [EmployeeAttendanceController::class, 'checkInV2']);
        Route::post('check-out-v2/{id}', [EmployeeAttendanceController::class, 'checkOutV2']);
        Route::post('check-out', [EmployeeAttendanceController::class, 'checkOut']);
        Route::put('approve/{id}', [EmployeeAttendanceController::class, 'approve']);
        Route::post('my-active-schedule', [EmployeeAttendanceController::class, 'getActiveeSchedule']);
        Route::get('attendance-evaluate', [EmployeeAttendanceController::class, 'getMonthlyEvaluate']);
        Route::get('list-approval', [EmployeeAttendanceController::class, 'listApproval']);
        Route::get('all-schedules', [EmployeeAttendanceController::class, 'getAllSchedules']);
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
        Route::get('operating-unit', [UnitController::class, 'operatingUnits']);
        Route::put('update/{id}', [UnitController::class, 'update']);
        Route::get('detail/{id}', [UnitController::class, 'detailUnit']);

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
        Route::get('all', [DepartmentController::class, 'all']);
        Route::put('create/{id}', [DepartmentController::class, 'assign']);
        Route::get('view/{id}/{unit_id}', [DepartmentController::class, 'show']);
        Route::post('assign-team/{id}/{unit_id}', [DepartmentController::class, 'assignTeam']);
    });
    // End Department

    // Begin Backups
    Route::group(['prefix' => 'backup'], function() {
        Route::get('', [BackupController::class, 'index']);
        Route::get('view/{id}', [BackupController::class, 'show']);
        Route::get('list-approval', [BackupController::class, 'getListApproval']);
        Route::get('list-employee-backup', [BackupController::class, 'listEmployeeBackupTime']);
        Route::get('get-active-backup/{id}', [BackupController::class, 'getActiveEmployeeEvent']);
        Route::get('view-employee/{id}', [BackupController::class, 'getDetailEmployeeBackup']);
        Route::post('create', [BackupController::class, 'create']);
        Route::delete('delete/{id}', [BackupController::class, 'delete']);
        Route::post('approval/{id}', [BackupController::class, 'approve']);
        Route::post('check-in/{id}', [BackupController::class, 'checkIn']);
        Route::post('check-out/{id}', [BackupController::class, 'checkOut']);
        Route::get('attendance-evaluate', [BackupController::class, 'monthlyEvaluate']);
    });
    // End Backups

    // Begin Incident
    Route::group(['prefix' => 'incident'], functioN() {
        Route::get('', [IncidentController::class, 'index']);
        Route::get('view/{incidentID}', [IncidentController::class, 'view']);
        Route::get('list-approvals', [IncidentController::class, 'listApproval']);
        Route::post('create', [IncidentController::class, 'create']);
        Route::post('approval/{incidentID}', [IncidentController::class, 'approval']);
        Route::post('closure/{incidentID}', [IncidentController::class, 'closure']);
        Route::post('upload-image', [IncidentController::class, 'uploadImage']);
    });
    // End Incident

    // Begin Event
    Route::group(['prefix' => 'event'], function() {
        Route::get('', [EventController::class, 'index']);
        Route::get('view/{id}', [EventController::class, 'view']);
        Route::get('employee-event', [EventController::class, 'employeeEvent']);
        Route::get('approvals', [EventController::class, 'listApproval']);
        Route::post('create', [EventController::class, 'create']);
        Route::post('/approval/{id}', [EventController::class, 'approval']);
        Route::post('/check-in/{id}', [EventController::class, 'checkIn']);
        Route::post('/check-out/{id}', [EventController::class, 'checkOut']);
        Route::post('/publish/{id}', [EventController::class, 'publish']);
        Route::post('/edit/{id}', [EventController::class, 'update']);
        Route::delete('/remove-attendance/{id}', [EventController::class, 'removeAttendance']);
        Route::post('/add-attendance/{id}', [EventController::class, 'addAttendance']);
        Route::get('get-active-event/{id}', [EventController::class, 'getActiveEmployeeEvent']);
        Route::get('attendance-evaluate', [EventController::class, 'monthlyEvaluate']);
    });
    // End Event

    // Begin Work Reporting
    Route::group(['prefix' => 'work-reporting'], function() {
        Route::get('', [WorkReportingController::class, 'index']);
        Route::get('view/{id}', [WorkReportingController::class, 'show']);
        Route::post('create', [WorkReportingController::class, 'store']);
        Route::post('create-mandatory', [WorkReportingController::class, 'createMandatoryWorkReporting']);
        Route::put('update/{id}', [WorkReportingController::class, 'edit']);
        Route::delete('delete/{id}', [WorkReportingController::class, 'delete']);
    });
    // End Work Reporting

    // Begin Job
    Route::group(['prefix' => 'job'], function() {
        Route::get('{id}', [JobController::class, 'index']);
        Route::get('', [JobController::class, 'allJobs']);
        Route::get('structured-job/data', [JobController::class, 'structuredJob']);
        Route::get('list/master-job', [JobController::class, 'getListMasterJob']);
        Route::get('show/{id}', [JobController::class, 'show']);
        Route::get('view/{id}', [JobController::class, 'view']);
        Route::post('save/{id}', [JobController::class, 'store']);
        Route::post('insert-pivot', [JobController::class, 'pivotInsert']);
        Route::put('update/{id}', [JobController::class, 'update']);
        Route::put('assign-roles/{id}', [JobController::class, 'assignRoles']);
        Route::put('update-mandatory/{id}', [JobController::class, 'updateMandatoryReporting']);
        Route::delete('delete/{unit_id}/{job_id}', [JobController::class, 'delete']);
        Route::delete('delete-assign/{id}', [JobController::class, 'deleteAssignJob']);
    });
    // End Job

    // Begin Overtime
    Route::group(['prefix' => 'overtime'], function () {
        Route::get('/', [OvertimeController::class, 'index']);
        Route::get('/view/{id}', [OvertimeController::class, 'view']);
        Route::get('list-approval', [OvertimeController::class, 'getListApproval']);
        Route::get('/view-employee/{id}', [OvertimeController::class, 'getDetailEmployeeOvertime']);
        Route::get('/employee-overtime', [OvertimeController::class, 'employee_overtimes']);
        Route::post('', [OvertimeController::class, 'create']);
        Route::post('approval/{id}', [OvertimeController::class, 'approval']);
        Route::post('check-in/{id}', [OvertimeController::class, 'checkIn']);
        Route::post('check-out/{id}', [OvertimeController::class, 'checkOut']);
        Route::get('get-active-overtime/{id}', [OvertimeController::class, 'getActiveOvertime']);
        Route::get('attendance-evaluate', [OvertimeController::class, 'monthlyEvaluate']);
        Route::delete('delete/{id}', [OvertimeController::class, 'deleteOvertime']);
    });
    // End Overtime

    // Begin Notification
    Route::group(['prefix' => 'notification'], function() {
        Route::get('', [NotificationController::class, 'index']);
    });
    // End Notificataion

    Route::group(['prefix' => 'admin_unit'], function() {
        Route::get('', [AdminUnitController::class, 'index']);
        Route::get('my', [AdminUnitController::class, 'myAdminUnits']);
        Route::post('create', [AdminUnitController::class, 'create']);
        Route::post('assign-multiple', [AdminUnitController::class, 'assignMultiple']);
        Route::delete('remove/{id}', [AdminUnitController::class, 'remove']);
    });

    // Begin Master Leave
    Route::group(['prefix' => 'master_leave'], function() {
        Route::get('', [MasterLeaveController::class, 'index']);
        Route::get('view/{id}', [MasterLeaveController::class, 'show']);
        Route::post('create', [MasterLeaveController::class, 'save']);
        Route::put('update/{id}', [MasterLeaveController::class, 'update']);
        Route::put('delete/{id}', [MasterLeaveController::class, 'delete']);
    });
    // End Master Leave

    // Begin Leave Request
    Route::group(['prefix' => 'leave_request'], function() {
        Route::get('', [LeaveRequestController::class, 'index']);
        Route::get('view/{id}', [LeaveRequestController::class, 'show']);
        Route::get('approvals', [LeaveRequestController::class, 'listApproval']);
        Route::post('create', [LeaveRequestController::class, 'save']);
        Route::put('approve/{id}', [LeaveRequestController::class, 'approve']);
        Route::put('reject/{id}', [LeaveRequestController::class, 'reject']);
        Route::post('approval/{id}', [LeaveRequestController::class, 'approval']);
        Route::post('upload', [LeaveRequestController::class, 'upload']);
    });
    // End Leave Request

    Route::group(['prefix' => 'kantor_perwakilan'], function() {
       Route::get('', [KantorPerwakilanController::class, 'index']);
       Route::get('view/{id}', [KantorPerwakilanController::class, 'view']);
    });

    Route::group(['prefix' => 'operating-unit'], function () {
        Route::get('', [OperatingUnitController::class, 'index']);
        Route::get('kanwils', [OperatingUnitController::class, 'kanwil']);
        Route::get('corporates', [OperatingUnitController::class, 'corporate']);
        Route::get('available-kanwil', [OperatingUnitController::class, 'availableKanwil']);
        Route::post('assign', [OperatingUnitController::class, 'assign']);
        Route::post('assign-user', [OperatingUnitController::class, 'assignUser']);
        Route::delete('remove/{id}', [OperatingUnitController::class, 'remove']);
        Route::post('remove-user', [OperatingUnitController::class, 'removeUser']);
        Route::post('assign-central', [OperatingUnitController::class, 'assignOperatingUnitCentralUser']);
        Route::delete('remove-central/{id}', [OperatingUnitController::class, 'removeOperatingUnitCentralUser']);
        Route::get('central-users', [OperatingUnitController::class, 'listOperatingUnitCentralUser']);
    });

    Route::group(['prefix' => 'team'], function () {
        Route::get('', [TeamController::class, 'index']);
        Route::get('view/{id}', [TeamController::class, 'show']);
        Route::post('create', [TeamController::class, 'save']);
        Route::put('update/{id}', [TeamController::class, 'update']);
        Route::delete('delete/{id}', [TeamController::class, 'delete']);
    });

    Route::group(['prefix' => 'unit-job'], function() {
        Route::get('', [UnitJobController::class, 'index']);
        Route::get('chart-view', [UnitJobController::class, 'chartView']);
        Route::post('assign', [UnitJobController::class, 'assign']);
        Route::post('create', [UnitJobController::class, 'create']);
    });

    Route::group(['prefix' => 'attendance-correction'], function() {
        Route::get('index', [AttendanceCorrectionController::class, 'index']);
        Route::get('view/{id}', [AttendanceCorrectionController::class, 'view']);
        Route::get('list-approval', [AttendanceCorrectionController::class, 'listApproval']);
        Route::post('create', [AttendanceCorrectionController::class, 'create']);
        Route::post('approval/{id}', [AttendanceCorrectionController::class, 'approval']);
    });

    Route::group(['prefix' => 'public_holiday'], function() {
        Route::get('', [PublicHolidayController::class, 'index']);
        Route::get('view/{id}', [PublicHolidayController::class, 'view']);
        Route::post('create', [PublicHolidayController::class, 'create']);
        Route::post('update/{id}', [PublicHolidayController::class, 'update']);
        Route::delete('delete/{id}', [PublicHolidayController::class, 'delete']);
    });

    Route::group(['prefix' => 'timesheet-report'], function () {
        Route::get('', [TimesheetReportController::class, 'index']);
        Route::get('view/{id}', [TimesheetReportController::class, 'view']);
        Route::get('list-timesheet-detail', [TimesheetReportController::class, 'listTimesheetDetail']);
        Route::post('', [TimesheetReportController::class, 'create']);
        Route::post('sync/{id}', [TimesheetReportController::class, 'sync']);
        Route::post('send-to-erp/{id}', [TimesheetReportController::class, 'sendToERP']);
        Route::delete('delete/{id}', [TimesheetReportController::class, 'delete']);
    });
});
