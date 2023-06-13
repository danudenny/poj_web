<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Services\BaseService;

class EmployeeService extends BaseService
{
    public function index($data)
    {
        try {
            $employee = Employee::query();
            if (!is_null($data->name)) {
                $employee->where('name', 'like', '%' . $data->name . '%');
            }
            return $this->list($employee, $data);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    public function view($id)
    {
        try {
            $employee = Employee::firstWhere('id', $id);

            if (!$employee) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return $employee;

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }
}
