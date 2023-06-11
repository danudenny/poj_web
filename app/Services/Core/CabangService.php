<?php

namespace App\Services\Core;

use App\Models\Cabang;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CabangService extends BaseService
{
    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function index($data): mixed
    {
        try {
            $cabang = Cabang::query();
            if (!is_null($data->name)) {
                $cabang->where('name', 'like', '%' . $data->name . '%');
            }

            return $this->list($cabang, $data);

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }

    }

    /**
     * @param $data
     * @return Builder|Model
     * @throws Exception
     */
    public function view($data): Builder|Model
    {
        try {
            $cabang = Cabang::firstWhere('id', $data['id']);

            if (!$cabang) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return $cabang;

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

}
