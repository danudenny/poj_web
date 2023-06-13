<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MinioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileUploadSampleController extends Controller
{
    private $minioService;

    public function __construct(MinioService $minioService)
    {
        $this->minioService = $minioService;
    }
    /**
     * Handle the file upload and store it in Minio.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $path = 'uploads';
        $uploadedPath = $this->minioService->uploadFile($file, $path);
        return response()->json(['path' => $uploadedPath]);
    }

    public function uploadMulti(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $files = $request->file('files');
        $path = 'uploads';
        $maxFiles = 10;

        if (count($files) <= $maxFiles) {
            $uploadedUrls = [];

            foreach ($files as $file) {
                $fullFilePath = 'uploads' . '/'. uniqid() .'_'. $file->getClientOriginalName();
                Storage::disk('s3')->put($fullFilePath, file_get_contents($file));
                $path = Storage::disk('s3')->url($fullFilePath);
                $uploadedUrls[] = $path;
            }

            return response()->json(['urls' => $uploadedUrls], 200);
        } else {
            return response()->json(['error' => 'Exceeded maximum file limit'], 400);
        }

    }

    public function getImages(): JsonResponse
    {
        $images = Storage::disk('minio')->files('images');
        return response()->json(['images' => $images], 200);
    }
}
