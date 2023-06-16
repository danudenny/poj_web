<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\WorkLocation;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeService extends BaseService
{
    /**
     * @throws Exception
     */
    public function index($data)
    {
        try {
            $employees = Employee::with('company', 'employeeTimesheet', 'employeeTimesheet.employeeDetails');
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
}
