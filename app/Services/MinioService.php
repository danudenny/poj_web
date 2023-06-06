<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MinioService
{
    public static function uploadFile(UploadedFile $file, $path): string
    {
        $bucket = config('filesystems.disks.minio.bucket');
        $url = config('filesystems.disks.minio.url');

        $tempPath = Storage::cloud()->putFileAs($path, $file, uniqid() .'_'. $file->getClientOriginalName());
        return $url . '/' . $bucket . '/' . $tempPath;
    }
}
