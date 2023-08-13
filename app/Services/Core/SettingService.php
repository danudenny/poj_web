<?php

namespace App\Services\Core;

use App\Models\Setting;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Database\QueryException;

class SettingService extends BaseService
{
    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function index(): JsonResponse
    {
        try {
            $settings = Setting::query();
            return response()->json([
                'status' => 'success',
                'message' => 'Success fetch data',
                'data' => $settings->get()
            ]);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @throws Exception
     */
    public function save($request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $setting = new Setting();
            $setting->key = $request->key;
            $setting->value = $request->value;

            if (!$setting->save()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Failed save data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success save data',
                'data' => $setting
            ], 201);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function update($request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $setting = Setting::firstWhere('id', $id);

            if (!$setting) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data not found'
                ], 404);
            }

            $setting->key = $request->key;
            $setting->value = $request->value;

            if (!$setting->save()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Failed save data'
                ], 500);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success save data',
                'data' => $setting
            ], 201);

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function bulkUpdate($request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $settings = $request->all();

            foreach ($settings as $settingData) {
                $setting = Setting::firstWhere('id', $settingData['id']);

                if (!$setting) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Data not found'
                    ], 404);
                }

                $setting->key = $settingData['key'];
                $setting->value = $settingData['value'];

                if (!$setting->save()) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Failed save data'
                    ], 500);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Success save data',
                'data' => $settings
            ], 201);

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function delete($id): mixed
    {
        DB::beginTransaction();
        try {
            $setting = Setting::find($id);
            if (!$setting) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $setting->delete();

            DB::commit();
            return $setting;

        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

}
