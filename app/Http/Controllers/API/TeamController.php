<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Core\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    private TeamService $teamService;
    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->teamService->index($request);
    }

    public function save(Request $request): JsonResponse
    {
        return $this->teamService->save($request);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->teamService->update($request, $id);
    }

    public function delete($id): JsonResponse
    {
        return $this->teamService->delete($id);
    }

    public function show($id): JsonResponse
    {
        return $this->teamService->show($id);
    }

}
