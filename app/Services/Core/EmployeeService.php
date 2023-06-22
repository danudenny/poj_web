<?php

namespace App\Services\Core;

use App\Jobs\SyncEmployeesJob;
use App\Models\Employee;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class EmployeeService extends BaseService
{
    /**
     * @throws Exception
     */
    public function index($data)
    {
        try {
            $employees = Employee::with('company', 'employeeDetail', 'employeeDetail.employeeTimesheet');
            $employees->when(request('name'), function ($query) {
                $query->where('name', 'like', '%' . request('name') . '%');
            });

            return $this->list($employees, $data);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function view($id): Model|Builder
    {
        try {
            $employee = Employee::with('company', 'company.workLocation', 'employeeDetail', 'employeeDetail.employeeTimesheet')->find($id);

            if (!$employee) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return $employee;

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    public function syncToUser(): JsonResponse
    {
        dispatch(new SyncEmployeesJob());
        return response()->json(['message' => 'Success']);

    }
}
