<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\Unit;
use App\Models\UnitReporting;
use App\Models\WorkReporting;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WorkReportingService
{
    function getLastUnit($data) {
        $bottomData = null;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nestedData = $this->getLastUnit($value);
                if ($nestedData !== null) {
                    $bottomData = $nestedData;
                }
            } elseif ($key === 'value' && $value !== null) {
                $bottomData = $data;
            }
        }

        return $bottomData;
    }

    public function index($request): JsonResponse
    {
        try {
            $roles = auth()->user()->roles()->pluck('role_level');

            $empData = Employee::with(['kanwil', 'area', 'cabang', 'outlet'])
                ->find(auth()->user()->employee_id);

            $decodedEmpData = json_decode($empData, true);
            $filteredUnitData = Arr::only($decodedEmpData, ['kanwil', 'area', 'cabang', 'outlet']);
            $empUnit = $this->getLastUnit($filteredUnitData);

            if ($roles->contains('superadmin')) {
                $workReporting = WorkReporting::query();
            } elseif ($roles->contains('admin')) {
                $workReporting = WorkReporting::query();
                if ($empUnit->unit_level === 3) {
                    $workReporting->where('employee.corporate_id', $empUnit->relation_id);
                } elseif ($empUnit->unit_level === 4) {
                    $workReporting->where('employee.kanwil_id', $empUnit->relation_id);
                } elseif ($empUnit->unit_level === 5) {
                    $workReporting->where('employee.area_id', $empUnit->relation_id);
                } elseif ($empUnit->unit_level === 6) {
                    $workReporting->where('employee.cabang_id', $empUnit->relation_id);
                } elseif ($empUnit->unit_level === 7) {
                    $workReporting->where('employee.outlet_id', $empUnit->relation_id);
                }
            } elseif ($roles->contains('staff')) {
                $workReporting = WorkReporting::query()->where('employee_id', auth()->user()->employee_id);
            }

            if ($request->has('title')) {
                $workReporting->where('title', 'like', '%' . $request->search . '%');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetch work reporting data',
                'data' => $workReporting->simplePaginate(10)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function save($request): JsonResponse
    {
        $empData = auth()->user()->employee;

        if ($empData->job_id === null || $empData->job_id === 0 || stripos($empData->job->name, "SATPAM") === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not required to report!'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $workReporting = new WorkReporting();
            $workReporting->title = $request->title;
            $workReporting->date = Carbon::now();
            $workReporting->job_type = $request->job_type;
            $workReporting->job_description = $request->job_description;
            $workReporting->image = $request->image;
            $workReporting->employee_id = auth()->user()->employee_id;

            if (!$workReporting->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save work reporting data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully save work reporting data',
                'data' => $workReporting
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function view($id): JsonResponse
    {
        try {
            $workReporting = WorkReporting::find($id);

            if (!$workReporting) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work reporting data not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetch work reporting data',
                'data' => $workReporting
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update($request, $id): JsonResponse
    {
        $workReporting = WorkReporting::find($id);
        if (!$workReporting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work reporting data not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $workReporting->title = $request->title;
            $workReporting->date = Carbon::now();;
            $workReporting->job_type = $request->job_type;
            $workReporting->job_description = $request->job_description;
            $workReporting->image = $request->image;
            $workReporting->employee_id = auth()->user()->employee_id;

            if (!$workReporting->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update work reporting data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully update work reporting data',
                'data' => $workReporting
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        $workReporting = WorkReporting::find($id);
        if (!$workReporting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work reporting data not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            if (!$workReporting->delete()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete work reporting data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully delete work reporting data'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createMandatoryWorkReporting($request) {
        DB::beginTransaction();
        try {
            $create = new UnitReporting();
            $create->unit_jobs_id = $request->unit_jobs_id;
            $create->type = $request->type;
            $create->total_reporting = $request->total_reporting;
            $create->reporting_names = json_encode($request->reporting_names);

            if (!$create->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch mandatory work reporting data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully create mandatory work reporting data',
                'data' => $create
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch mandatory work reporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
