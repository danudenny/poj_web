<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Core\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IncidentController extends Controller
{
    public function index(Request $request, IncidentService $service) {
        return $service->index($request);
    }

    public function view(Request $request, IncidentService $service, int $incidentID) {
        return $service->view($incidentID);
    }

    public function create(Request $request, IncidentService $service): JsonResponse {
        return $service->createIncident($request);
    }

    public function approval(Request $request, IncidentService $service, int $incidentID): JsonResponse {
        return $service->incidentApproval($request, $incidentID);
    }

    public function closure(Request $request, IncidentService $service, int $incidentID): JsonResponse {
        return $service->closure($request, $incidentID);
    }

    public function uploadImage(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $files = $request->file('files');
        $path = 'uploads/incident';

        $uploadedUrls = [];

        foreach ($files as $file) {
            $fullFilePath = $path . '/'. uniqid() .'_'. $file->getClientOriginalName();
            Storage::disk('s3')->put($fullFilePath, file_get_contents($file));
            $path = Storage::disk('s3')->url($fullFilePath);
            $uploadedUrls[] = $path;
        }

        return response()->json(['urls' => $uploadedUrls], 200);
    }
}
