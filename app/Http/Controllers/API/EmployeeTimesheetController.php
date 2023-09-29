<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\EmployeeTimesheet\CreateEmployeeTimesheetRequest;
use App\Http\Requests\EmployeeTimesheet\UpdateEmployeeTimesheetRequest;
use App\Http\Requests\EmployeeTimesheet\UpdateEmployeeTimesheetScheduleRequest;
use App\Http\Requests\Timesheet\CreateTimesheetRequest;
use App\Services\Core\EmployeeTimesheetService;
use App\Services\Core\PeriodService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Psr\Container\ContainerExceptionInterface;

class EmployeeTimesheetController extends BaseController
{
    private EmployeeTimesheetService $employeeTimesheetService;

    public function __construct(EmployeeTimesheetService $employeeTimesheetService) {
        $this->employeeTimesheetService = $employeeTimesheetService;
    }

    /**
     * @throws Exception
     */
    public function index(Request $request, $id): JsonResponse
    {
        try {
            return $this->employeeTimesheetService->index($request, $id);
        } catch (Exception|ContainerExceptionInterface $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function save(CreateTimesheetRequest $request, $id): JsonResponse
    {
        try {
            return $this->employeeTimesheetService->save($request, $id);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function view($unit_id,$id): JsonResponse
    {
        try {
            return $this->employeeTimesheetService->show($unit_id,$id);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function edit(Request $request, $id): JsonResponse
    {
        try {
            return $this->employeeTimesheetService->update($request, $id);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function delete($id): JsonResponse
    {
        try {
            return $this->employeeTimesheetService->delete($id);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function assignSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->assignTimesheetSchedule($request);
    }
    public function reAssignSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->reassignOrUpdateTimesheetSchedule($request);
    }

    public function getEmployeeSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->getEmployeeSchedule($request);
    }

    public function showEmployeeSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->showEmployeeSchedule($request);
    }

    public function scheduleById(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->scheduleById($request);
    }

    public function updateEmployeeSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->updateSchedule($request);
    }

    public function deleteEmployeeSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->deleteSchedule($request);
    }

    public function indexSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->indexSchedule($request);
    }

    public function deleteEmployeeTimesheet(Request $request, int $id) {
        return $this->employeeTimesheetService->deleteEmployeeTimesheetSchedule($request, $id);
    }

    public function viewEmployeeTimesheetSchedule(Request $request, int $id) {
        return $this->employeeTimesheetService->view($request, $id);
    }

    public function updateEmployeeTimesheetSchedule(UpdateEmployeeTimesheetScheduleRequest $request, int $id) {
        return $this->employeeTimesheetService->updateEmployeeTimesheet($request, $id);
    }

    public function syncNonShiftSchedule(Request $request) {
        $now = Carbon::now()->setTimezone(getClientTimezone());
        $status = Artisan::call('sync:non-shift-schedule', [
            'startDate' => '2023-10-01',
//            'startDate' => $now->format('Y-m-d'),
        ]);

        if ($status == Command::SUCCESS) {
            return response()->json([
                'status' => true,
                'message' => 'Sukses melakukan sync!'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Gagal melakukan sync!'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
