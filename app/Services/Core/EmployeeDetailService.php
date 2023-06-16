<?php

namespace App\Services\Core;

use App\Models\EmployeeDetail;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EmployeeDetailService extends BaseService
{
    /**
     * @throws Exception
     */
    public function create($data): JsonResponse
    {
        DB::beginTransaction();
        try {
            $create = new EmployeeDetail();
            $create->employee_id = $data['employee_id'];
            $create->employee_timesheet_id = $data['employee_timesheet_id'];

            if(!$create->save()) {
                throw new Exception('Failed to create employee detail');
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Employee detail created successfully',
                'data' => $create
            ], 201);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
