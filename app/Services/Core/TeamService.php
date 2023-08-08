<?php

namespace App\Services\Core;

use App\Models\Team;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TeamService extends BaseService
{
    private Team $team;
    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    public function index($request): JsonResponse
    {
        $teams = $this->team
            ->withCount('departments')
            ->when($request->name, function ($query) use ($request) {
                $query->whereRaw("LOWER(name) LIKE '%" . strtolower($request->name) . "%'");
            })
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => 200,
            'message' => 'Data retrieved successfully',
            'data' => $teams
        ]);
    }

    public function save($request): JsonResponse
    {
        $team = $this->team->whereRaw("LOWER(name) = '" . strtolower($request->name) . "'")->first();
        if($team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team already exists',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $team = $this->team->create([
                'name' => $request->name,
                'description' => $request->description
            ]);

            if (!$team) {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to save team',
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 200,
                'message' => 'Team saved successfully',
                'data' => $team
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function show($id): JsonResponse
    {
        $team = $this->team->find($id);
        if (!$team) {
            return response()->json([
                'status' => 404,
                'message' => 'Team not found',
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Data retrieved successfully',
            'data' => $team
        ]);
    }

    public function update($request, $id): JsonResponse
    {
        $team = $this->team->find($id);
        if (!$team) {
            return response()->json([
                'status' => 404,
                'message' => 'Team not found',
            ]);
        }

        $team = $this->team->where('id', '!=', $id)->whereRaw("LOWER(name) LIKE '%" . strtolower($request->name) . "%'")->first();
        if($team) {
            return response()->json([
                'status' => 400,
                'message' => 'Team already exists',
            ]);
        }

        DB::beginTransaction();
        try {
            $team = $this->team->where('id', '=', $id)->update([
                'name' => $request->name,
                'description' => $request->description
            ]);

            if (!$team) {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to update team',
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 200,
                'message' => 'Team updated successfully',
                'data' => $team
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function delete($id): JsonResponse
    {
        $team = $this->team->find($id);
        if (!$team) {
            return response()->json([
                'status' => 404,
                'message' => 'Team not found',
            ]);
        }

        DB::beginTransaction();
        try {
            $team->departments()->detach();
            $team = $this->team->where('id', '=', $id)->delete();

            if (!$team) {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to delete team',
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 200,
                'message' => 'Team deleted successfully',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
