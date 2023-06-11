<?php

namespace App\Services\Core;

use App\Models\Corporate;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CorporateService extends BaseService
{
    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function index($data): mixed
    {
        try {
            $corp = Corporate::query();
            if (!is_null($data->name)) {
                $corp->where('name', 'like', '%' . $data->name . '%');
            }

            return $this->list($corp, $data);

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
            $corp = Corporate::firstWhere('id', $data['id']);

            if (!$corp) {
                throw new InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return $corp;

        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

}
