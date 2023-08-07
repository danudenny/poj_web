<?php

namespace App\Services\Core;

use App\Models\KantorPerwakilan;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

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

    public function view(Request $request, int $id) {
        $kantorPerwakilan = KantorPerwakilan::query()
            ->where('id', '=', $id)
            ->first();
        if (!$kantorPerwakilan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit not found'
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'data' => $kantorPerwakilan
        ]);
    }
}
