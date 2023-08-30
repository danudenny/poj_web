<?php

namespace App\Services\Core;

use App\Http\Requests\PublicHoliday\UpdateInsertPublicHolidayRequest;
use App\Models\PublicHoliday;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PublicHolidayService extends BaseService
{
    public function index(Request $request) {
        $query = PublicHoliday::query()
            ->orderBy('holiday_date', 'DESC');

        return response()->json([
            'status' => true,
            'data' => $this->list($query, $request)
        ]);
    }

    public function view(Request $request, int $id) {
        /**
         * @var PublicHoliday $publicHoliday
         */
        $publicHoliday = PublicHoliday::query()
            ->where('id', '=', $id)
            ->first();
        if (!$publicHoliday) {
            return response()->json([
                'status' => false,
                'message' => 'Data not found!'
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => true,
            'data' => $publicHoliday
        ]);
    }

    public function createPublicHoliday(UpdateInsertPublicHolidayRequest $request) {
        try {
            $user = $request->user();

            $isDateExist = PublicHoliday::query()
                ->where('holiday_date', '=', $request->input('date'))
                ->exists();
            if ($isDateExist) {
                return response()->json([
                    'status' => false,
                    'message' => 'Date already exist'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $publicHoliday = new PublicHoliday();
            $publicHoliday->holiday_date = $request->input('date');
            $publicHoliday->holiday_type = $request->input('type');
            $publicHoliday->name = $request->input('name');
            $publicHoliday->is_shift = $request->input('is_shift');
            $publicHoliday->is_non_shift = $request->input('is_non_shift');
            $publicHoliday->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePublicHoliday(UpdateInsertPublicHolidayRequest $request, int $id) {
        try {
            $user = $request->user();

            /**
             * @var PublicHoliday $publicHoliday
             */
            $publicHoliday = PublicHoliday::query()
                ->where('id', '=', $id)
                ->first();
            if (!$publicHoliday) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $publicHoliday->holiday_date = $request->input('date');
            $publicHoliday->holiday_type = $request->input('type');
            $publicHoliday->name = $request->input('name');
            $publicHoliday->is_shift = $request->input('is_shift');
            $publicHoliday->is_non_shift = $request->input('is_non_shift');
            $publicHoliday->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Request $request, int $id) {
        try {
            /**
             * @var PublicHoliday $publicHoliday
             */
            $publicHoliday = PublicHoliday::query()
                ->where('id', '=', $id)
                ->first();
            if (!$publicHoliday) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();
            $publicHoliday->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $publicHoliday
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
