<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

        if ($request->per_page) {
            return $model->paginate($request->per_page)->withQueryString();
        } else {
            return $model->get();
        }
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

    /**
     * convert base64 format to file
     * @param string $base64File
     * @return UploadedFile
     */
    protected function fromBase64(string $base64File): UploadedFile
    {
        // Get file data base64 string
        $fileData = base64_decode(Arr::last(explode(',', $base64File)));

        // Create temp file and get its absolute path
        $tempFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        // Save file data in file
        file_put_contents($tempFilePath, $fileData);

        $tempFileObject = new File($tempFilePath);
        $file = new UploadedFile(
            $tempFileObject->getPathname(),
            $tempFileObject->getFilename(),
            $tempFileObject->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );

        // Close this file after response is sent.
        // Closing the file will cause to remove it from temp director!
        app()->terminating(function () use ($tempFile) {
            fclose($tempFile);
        });

        // return UploadedFile object
        return $file;
    }
}
