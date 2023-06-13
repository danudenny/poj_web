<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class MinioService
{
    public function uploadFile($file, $path): string
    {
        $fullFilePath = $path . '/' . uniqid() . '_' . $file->getClientOriginalName();
        Storage::disk('s3')->put($fullFilePath, file_get_contents($file));
        return Storage::disk('s3')->url($fullFilePath);
    }
}
