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
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function index($data): mixed
    {
        try {
            $settings = Setting::query();
            return $this->list($settings, $data);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @throws Exception
     */
    public function save($request): Setting
    {
        DB::beginTransaction();
        try {
            $setting = new Setting();
            $setting->key = $request->key;
            $setting->value = $request->value;

            if (!$setting->save()) {
                throw new \Exception(self::DB_FAILED, 500);
            }

            DB::commit();
            return $setting;
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function update($request, $id): mixed
    {
        DB::beginTransaction();
        try {
            $setting = Setting::firstWhere('id', $id);

            if (!$setting) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }

            $setting->key = $request->key;
            $setting->value = $request->value;

            if (!$setting->save()) {
                throw new \Exception(self::DB_FAILED, 500);
            }

            DB::commit();
            return $setting;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
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
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            $setting->delete();

            DB::commit();
            return $setting;

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

}
