<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\CreateEventRequest;
use App\Http\Requests\Event\EventApprovalRequest;
use App\Services\Core\EventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request, EventService $service) {
        return $service->index($request);
    }

    public function employeeEvent(Request $request, EventService $service) {
        return $service->getEmployeeEvents($request);
    }

    public function view(Request $request, EventService $service, int $id) {
        return $service->view($request, $id);
    }

    public function create(CreateEventRequest $request, EventService $service) {
        return $service->createEvent($request);
    }

    public function approve(EventApprovalRequest $request, EventService $service, int $id) {
        return $service->eventApproval($request, $id);
    }
}
