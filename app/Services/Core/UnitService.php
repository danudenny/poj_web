<?php

namespace App\Services\Core;

use App\Models\Unit;
use App\Services\BaseService;

class UnitService extends BaseService
{
    /**
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function index($data): mixed
    {
        try {
            $unit = Unit::query()->with(['parent', 'child', 'level'])
            ->whereHas('level', function($query) use($data) {
                $query->where('name', $data->level_name);
            });

            $unit->when(request()->filled('name'), function ($query) {
                $query->where('name', 'like',  '%'.request()->query('name').'%');
            });

            return $this->list($unit, $data);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function view($data): mixed
    {
        try {
            $unit = Unit::with(['level', 'parent', 'child'])
            ->firstWhere('id', $data['id']);

            if (!$unit) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return $unit;

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }
}
