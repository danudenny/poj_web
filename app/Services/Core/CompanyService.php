<?php

namespace App\Services\Core;

use App\Http\Resources\RoleResource;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyService extends BaseService
{
    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function index($data): mixed
    {
        try {
            $companies = Company::query();
            if (!is_null($data->name)) {
                $companies->where('name', 'like', '%' . $data->name . '%');
            }

            return $this->list($companies, $data);

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
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
            $company = Company::firstWhere('id', $data['id']);

            if (!$company) {
                throw new \InvalidArgumentException(self::DATA_NOTFOUND, 400);
            }
            return $company;

        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        } catch (Exception $e) {
            throw new Exception(self::SOMETHING_WRONG.' : '.$e->getMessage());
        }
    }

}
