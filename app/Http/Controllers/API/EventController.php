<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\AddNewAttendanceRequest;
use App\Http\Requests\Event\CheckInEventRequest;
use App\Http\Requests\Event\CheckOutEventRequest;
use App\Http\Requests\Event\CreateEventRequest;
use App\Http\Requests\Event\EventApprovalRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
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

    public function listApproval(Request $request, EventService $service) {
        return $service->listApproval($request);
    }

    public function create(CreateEventRequest $request, EventService $service) {
        return $service->createEvent($request);
    }

    public function approval(EventApprovalRequest $request, EventService $service, int $id) {
        return $service->eventApproval($request, $id);
    }

    public function publish(Request $request, EventService $service, int $id) {
        return $service->publish($request, $id);
    }

    public function update(UpdateEventRequest $request, EventService $service, int $id) {
        return $service->updateEvent($request, $id);
    }

    public function checkIn(CheckInEventRequest $request, EventService $service, int $id) {
        return $service->checkIn($request, $id);
    }

    public function checkOut(CheckOutEventRequest $request, EventService $service, int $id) {
        return $service->checkOut($request, $id);
    }

    public function getActiveEmployeeEvent(Request $request, EventService $service, int $id) {
        return $service->getActiveEventEmployee($request, $id);
    }

    public function removeAttendance(Request $request, EventService $service, int $id) {
        return $service->removeAttendance($request, $id);
    }

    public function addAttendance(AddNewAttendanceRequest $request, EventService $service, int $id) {
        return $service->addNewAttendance($request, $id);
    }

    public function monthlyEvaluate(Request $request, EventService $service) {
        return $service->monthlyEvaluate($request);
    }
}
