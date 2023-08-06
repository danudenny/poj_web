<?php

namespace App\Services\Core;

use App\Models\KantorPerwakilan;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;

class KantorPerwakilanService extends BaseService {

    private KantorPerwakilan $kantorPerwakilan;
    public function __construct(KantorPerwakilan $kantorPerwakilan) {
        $this->kantorPerwakilan = $kantorPerwakilan;
    }

    public function index($request): JsonResponse
    {
        $kantorPerwakilan = $this->kantorPerwakilan->query();
        $kantorPerwakilan->when('name', function ($query) use ($request) {
            $query->whereRaw("LOWER(name) LIKE '%" . strtolower($request->name) . "%'");
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data kantor perwakilan berhasil diambil',
            'data' => $kantorPerwakilan->paginate($request->per_page ?? 10)
        ]);
    }
}
