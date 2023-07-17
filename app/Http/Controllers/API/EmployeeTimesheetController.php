<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\EmployeeTimesheet\CreateEmployeeTimesheetRequest;
use App\Http\Requests\EmployeeTimesheet\UpdateEmployeeTimesheetRequest;
use App\Services\Core\EmployeeTimesheetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request): JsonResponse
    {
        try {
            return $this->employeeTimesheetService->index($request);
        } catch (Exception|ContainerExceptionInterface $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function save(CreateEmployeeTimesheetRequest $request): JsonResponse
    {
        try {
            return $this->employeeTimesheetService->save($request);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function view($id): JsonResponse
    {
        try {
            return $this->employeeTimesheetService->show($id);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function edit(UpdateEmployeeTimesheetRequest $request, $id): JsonResponse
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

    public function getEmployeeSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->getEmployeeSchedule($request);
    }

    public function showEmployeeSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->showEmployeeSchedule($request);
    }

    public function showEmployeeScheduleById(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->showEmployeeScheduleById($request);
    }

    public function updateEmployeeSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->updateSchedule($request);
    }

    public function deleteEmployeeSchedule(Request $request): JsonResponse
    {
        return $this->employeeTimesheetService->deleteSchedule($request);
    }

    public function getPeriods(): JsonResponse
    {
        return $this->employeeTimesheetService->getPeriods();
    }

}
