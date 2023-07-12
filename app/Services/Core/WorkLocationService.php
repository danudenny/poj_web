<?php

namespace App\Services\Core;

use App\Models\WorkLocation;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkLocationService extends BaseService
{

    public function index($request): JsonResponse
    {
        try {
            $workLocation = WorkLocation::query();
            $workLocation->with('company');
            $workLocation->where('reference_id', '=', $request->unit_id);
            return response()->json([
                'status' => true,
                'message' => 'Success fetch data!',
                'data' => $workLocation->paginate($request->query('limit') ?? 10)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed fetch data!',
                'data' => $e->getMessage()
            ]);
        }
    }

    public function save($request) {
        DB::beginTransaction();
        try {
            $workLocation = new WorkLocation();
            $workLocation->reference_table = $request->reference_table;
            $workLocation->reference_id = $request->reference_id;
            $workLocation->lat = $request->lat;
            $workLocation->long = $request->long;
            $workLocation->radius = $request->radius;

            if (!$workLocation->save()) {
                throw new Exception('Failed save data!');
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success save data!',
                'data' => $workLocation
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed save data!',
                'data' => $e->getMessage()
            ]);
        }
    }

    public function show($request): JsonResponse
    {
        $dataExists = DB::table('work_locations')
            ->leftJoin('units', 'work_locations.reference_id', '=', 'units.relation_id')
            ->where('unit_level', '=', $request->unit_id)
            ->first();

        if (!$dataExists) {
            return response()->json([
                'status' => false,
                'message' => 'Data not found!',
                'data' => null
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Success fetch data!',
            'data' => $dataExists
        ]);
    }

    public function edit($data) {
        $dataExists = WorkLocation::where('reference_id', $data->unit_id)->first();
        if (!$dataExists) {
            return response()->json([
                'status' => false,
                'message' => 'Data not found!',
                'data' => null
            ]);
        }

        DB::beginTransaction();
        try {
            $dataExists->reference_table = '';
            $dataExists->reference_id = $data->unit_id;
            $dataExists->lat = $data->lat;
            $dataExists->long = $data->long;
            $dataExists->radius = $data->radius;
            $dataExists->early_buffer = $data->early_buffer;
            $dataExists->late_buffer = $data->late_buffer;

            if (!$dataExists->save()) {
                throw new Exception('Failed save data!');
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success save data!',
                'data' => $dataExists
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed save data!',
                'data' => $e->getMessage()
            ]);
        }
    }

    public function delete($id): JsonResponse
    {
        $dataExists = WorkLocation::find($id);
        if (!$dataExists) {
            return response()->json([
                'status' => false,
                'message' => 'Data not found!',
                'data' => null
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success delete data!',
            'data' => $dataExists->delete()
        ]);
    }
}
