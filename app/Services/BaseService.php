<?php

namespace App\Services;

abstract class BaseService
{
    public const DATA_NOTFOUND = 'Data not found!';
    public const DB_FAILED = 'Failed transaction DB!';
    public const AUTH_FAILED = 'Authentication Failed';
    public const SOMETHING_WRONG = 'Something Went Wrong';
    public const NOT_REGISTERED = 'Email is not registered';


    /**
     * Check data validation
     * @param array $data
     * @param array $rules
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function validateData(array $data, array $rules): void
    {
        $validator = validator()->make($data, $rules);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors());
        }
    }

    /**
     * @param $model
     * @param $request
     * @return mixed
     */
    protected function list($model, $request): mixed
    {
        if ($request->sort) {
            $order_type = 'asc';
            $order_column = $request->sort;
            if (str_contains($request->sort, '-')) {
                $order_type = 'desc';
                $order_column = substr($request->sort, 1);
            }

            if (!is_null($request->trash)) {
                $model->onlyTrashed();
            }

            $model->orderBy($order_column, $order_type);
        }

        return $model->paginate($request->per_page);
    }

    /**
     * Deactivated/Reactivated data model
     * @param $data
     * @return void
     */
    protected function toggleDataStatus($data)
    {
        if ($data->is_active == 1) {
            $data->is_active = 0;
        } else {
            $data->is_active = 1;
        }
        return $data->save();
    }
}
