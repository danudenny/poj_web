<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicHoliday\UpdateInsertPublicHolidayRequest;
use App\Services\Core\PublicHolidayService;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    private PublicHolidayService $service;

    public function __construct(PublicHolidayService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request) {
        return $this->service->index($request);
    }

    public function view(Request $request, int $id) {
        return $this->service->view($request, $id);
    }

    public function create(UpdateInsertPublicHolidayRequest $request) {
        return $this->service->createPublicHoliday($request);
    }

    public function update(UpdateInsertPublicHolidayRequest $request, int $id) {
        return $this->service->updatePublicHoliday($request, $id);
    }

    public function delete(Request $request, int $id) {
        return $this->service->delete($request, $id);
    }
}
