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
    /**
     * Handle the file upload and store it in Minio.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = 'uploads';

            $storeUrl = MinioService::uploadFile($file, $path);

            return response()->json(['url' => $storeUrl], 200);
        }


        return response()->json(['error' => 'File not found'], 400);
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
                $storeUrl = MinioService::uploadFile($file, $path);
                $uploadedUrls[] = $storeUrl;
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
