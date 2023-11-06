<?php

namespace App\Services\Core;

use App\Http\Requests\Event\AddNewAttendanceRequest;
use App\Http\Requests\Event\CheckInEventRequest;
use App\Http\Requests\Event\CheckOutEventRequest;
use App\Http\Requests\Event\CreateEventRequest;
use App\Http\Requests\Event\EventApprovalRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\ApprovalModule;
use App\Models\ApprovalUser;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeEvent;
use App\Models\EmployeeNotification;
use App\Models\Event;
use App\Models\EventApproval;
use App\Models\EventAttendance;
use App\Models\EventDate;
use App\Models\EventHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EventService extends BaseService
{
    private ApprovalService $approvalService;

    public function __construct()
    {
        $this->approvalService = new ApprovalService();
    }

    public function index(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $unitRelationID = $request->get('unit_relation_id');

        $query = Event::query()->with(['requestorEmployee:employees.id,name']);
        $query->join('event_attendances', 'event_attendances.event_id', '=', 'events.id');
        $query->join('employees', 'employees.id', '=', 'event_attendances.employee_id');
        $query->join('employees AS reqEmployee', 'reqEmployee.id', '=', 'events.requestor_employee_id');

        $query->when($request->filled('status'), function(Builder $builder) use ($request) {
            $builder->where('events.last_status', '=', $request->input('status'));
        });

        if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

        } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
            if (!$unitRelationID) {
                $defaultUnitRelationID = $user->employee->unit_id;

                if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                    $defaultUnitRelationID = $requestUnitRelationID;
                }

                $unitRelationID = $defaultUnitRelationID;
            }
        } else {
            $query->where('events.requestor_employee_id', '=', $user->employee_id);
        }

        if ($unitRelationID) {
            $query->where(function(Builder $builder) use ($unitRelationID) {
                $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                        ->orWhere('employees.cabang_id', '=', $unitRelationID)
                        ->orWhere('employees.area_id', '=', $unitRelationID)
                        ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                        ->orWhere('employees.corporate_id', '=', $unitRelationID);
                })->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('reqEmployee.outlet_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.cabang_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.area_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.kanwil_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.corporate_id', '=', $unitRelationID);
                });
            });
        }

        $query->orderBy('events.id', 'DESC');
        $query->select(['events.*']);
        $query->groupBy('events.id');

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $query->get()
        ]);
    }

    public function view(Request $request, int $id) {
        /**
         * @var Event $event
         */
        $event = Event::query()->with([
            'eventDates',
            'eventAttendances', 'eventAttendances.employee:employees.id,name,outlet_id,cabang_id,area_id,kanwil_id,corporate_id',
            'eventHistories', 'eventHistories.employee:employees.id,name'
        ])->find($id);
        if (is_null($event)) {
            return response()->json([
                'status' => false,
                'message' => 'Event is not found!'
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $event
        ]);
    }

    public function listApproval(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $unitRelationID = $request->get('unit_relation_id');

        $query = EventApproval::query()->with(['event', 'event.requestorEmployee', 'employee']);
        $query->join('events', 'events.id', '=', 'event_approvals.event_id');
        $query->join('event_attendances', 'event_attendances.event_id', '=', 'events.id');
        $query->join('employees', 'employees.id', '=', 'event_attendances.employee_id');
        $query->join('employees AS reqEmployee', 'reqEmployee.id', '=', 'events.requestor_employee_id');
        $query->join('employees AS approvalEmployee', 'approvalEmployee.id', '=', 'event_approvals.employee_id');

        if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

        } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
            if (!$unitRelationID) {
                $defaultUnitRelationID = $user->employee->unit_id;

                if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                    $defaultUnitRelationID = $requestUnitRelationID;
                }

                $unitRelationID = $defaultUnitRelationID;
            }
        } else {
            $query->where('event_approvals.employee_id', '=', $user->employee_id);
        }

        if ($unitRelationID) {
            $query->where(function(Builder $builder) use ($unitRelationID) {
                $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                        ->orWhere('employees.cabang_id', '=', $unitRelationID)
                        ->orWhere('employees.area_id', '=', $unitRelationID)
                        ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                        ->orWhere('employees.corporate_id', '=', $unitRelationID);
                })->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('reqEmployee.outlet_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.cabang_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.area_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.kanwil_id', '=', $unitRelationID)
                        ->orWhere('reqEmployee.corporate_id', '=', $unitRelationID);
                })->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('approvalEmployee.outlet_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.cabang_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.area_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.kanwil_id', '=', $unitRelationID)
                        ->orWhere('approvalEmployee.corporate_id', '=', $unitRelationID);
                });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('event_approvals.status', '=', $status);
        }

        $query->select(['event_approvals.*']);
        $query->groupBy('event_approvals.id');
        $query->orderBy('event_approvals.id', 'DESC');

        return response()->json([
            'status' => true,
            'message' => 'Success!',
            'data' => $this->list($query, $request)
        ]);
    }

    public function createEvent(CreateEventRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $event = new Event();
            $event->requestor_employee_id = $user->employee_id;
            $event->last_status = Event::StatusDraft;
            $event->event_type = $request->input('event_type');
            $event->image_url = $request->input('image_url');
            $event->title = $request->input('title');
            $event->description = $request->input('description');
            $event->latitude = $request->input('latitude');
            $event->longitude = $request->input('longitude');
            $event->location_type = $request->input('location_type');
            $event->address = $request->input('address');
            $event->date_event = $request->input('date_event');
            $event->time_event = $request->input('time_event');
            $event->is_need_absence = (bool) $request->input('is_need_absence');
            $event->is_repeat = (bool) $request->input('is_repeat');
            $event->repeat_type = $request->input('repeat_type');
            $event->repeat_every = $request->input('repeat_every');
            $event->repeat_days = $request->input('repeat_days');
            $event->repeat_end_date = $request->input('repeat_end_date');

            /**
             * @var int[] $eventAttendances
             */
            $eventAttendances = $request->input('event_attendances', []);

            if ($event->is_repeat && $event->repeat_end_date == '') {
                return response()->json([
                    'status' => false,
                    'message' => 'End date is required!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }
            if ($event->is_repeat && (Carbon::parse($event->repeat_end_date)->lessThan(Carbon::parse($event->date_event)))) {
                return response()->json([
                    'status' => false,
                    'message' => 'End date cannot less than event date!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }
            if ($event->is_repeat && (Carbon::parse($event->repeat_end_date)->lessThan(Carbon::today()))) {
                return response()->json([
                    'status' => false,
                    'message' => 'End date cannot less than today!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $eventDates = $this->generateEventDate($event);
            if (count($eventDates) == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no event dates!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $timezone = getTimezoneV2($event->latitude, $event->longitude);
            foreach ($eventDates as $idx => $eventDate) {
                $eventDateTime = Carbon::parse(sprintf("%s %s", $eventDate->event_date, $eventDate->event_time), $timezone)->setTimezone('UTC');
                $eventDate->event_datetime = $eventDateTime->format('Y-m-d H:i:s');
                unset($eventDate->event_date);
                unset($eventDate->event_time);
            }

            DB::beginTransaction();

            $event->timezone = $timezone;
            $event->save();

            foreach ($eventDates as $eventDate) {
                $eventDate->event_id = $event->id;
                $eventDate->save();
            }

            foreach ($eventAttendances as $employeeID) {
                $eventAttendance = new EventAttendance();
                $eventAttendance->event_id = $event->id;
                $eventAttendance->employee_id = $employeeID;
                $eventAttendance->save();
            }

            $eventHistory = new EventHistory();
            $eventHistory->event_id = $event->id;
            $eventHistory->employee_id = $user->employee_id;
            $eventHistory->status = $event->last_status;
            $eventHistory->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $event
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateEvent(UpdateEventRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var Event $event
             */
            $event = Event::query()
                ->where('requestor_employee_id', '=', $user->employee_id)
                ->where('last_status', '=', Event::StatusDraft)
                ->where('id', '=', $id)
                ->first();
            if (is_null($event)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Event is not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $isChangeSchedule = (bool) $request->input('is_change_schedule', false);
            if ($isChangeSchedule) {
                $event->is_repeat = (bool) $request->input('is_repeat');
                $event->repeat_type = $request->input('repeat_type');
                $event->repeat_every = $request->input('repeat_every');
                $event->repeat_days = $request->input('repeat_days');
                $event->repeat_end_date = $request->input('repeat_end_date');

                if ($event->is_repeat && $event->repeat_end_date == '') {
                    return response()->json([
                        'status' => false,
                        'message' => 'End date is required!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
                if ($event->is_repeat && (Carbon::parse($event->repeat_end_date)->lessThan(Carbon::parse($event->date_event)))) {
                    return response()->json([
                        'status' => false,
                        'message' => 'End date cannot less than event date!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
                if ($event->is_repeat && (Carbon::parse($event->repeat_end_date)->lessThan(Carbon::today()))) {
                    return response()->json([
                        'status' => false,
                        'message' => 'End date cannot less than today!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                $isSuccess = EventDate::query()->where('event_id', '=', $event->id)->delete();

                $eventDates = $this->generateEventDate($event);
                if (count($eventDates) == 0) {
                    return response()->json([
                        'status' => false,
                        'message' => 'There is no event dates!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                foreach ($eventDates as $eventDate) {
                    $eventDateTime = Carbon::parse(sprintf("%s %s", $eventDate->event_date, $eventDate->event_time), $event->timezone)->setTimezone('UTC');
                    $eventDate->event_id = $event->id;
                    $eventDate->event_datetime = $eventDateTime->format('Y-m-d H:i:s');

                    unset($eventDate->event_date);
                    unset($eventDate->event_time);

                    $eventDate->save();
                }
            }

            if ($eventType = $request->input('event_type')) {
                $event->event_type = $eventType;
            }
            if ($imageURL = $request->input('image_url')) {
                $event->image_url = $imageURL;
            }
            if ($title = $request->input('title')) {
                $event->title = $title;
            }
            if ($description = $request->input('description')) {
                $event->description = $description;
            }

            $event->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeAttendance(Request $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var EventAttendance $eventAttendance
             */
            $eventAttendance = EventAttendance::query()->where('id', '=', $id)->first();
            if (!$eventAttendance) {
                return response()->json([
                    'status' => false,
                    'message' => 'Event attendance is not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            if ($user->isHighestRole(Role::RoleStaff)) {
                if ($eventAttendance->event->requestor_employee_id != $user->employee_id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You don\'t have access to delete this attendance'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            if ($eventAttendance->event->last_status != Event::StatusDraft) {
                return response()->json([
                    'status' => false,
                    'message' => 'Event already published'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();
            $eventAttendance->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addNewAttendance(AddNewAttendanceRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var Event $event
             */
            $event = Event::query()->where('id', '=', $id)
                ->where('last_status', '=', Event::StatusDraft)
                ->first();
            if (!$event) {
                return response()->json([
                    'status' => false,
                    'message' => 'Event is not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            /**
             * @var int[] $employeeIDs
             */
            $employeeIDs = $request->input('employee_ids', []);

            foreach ($employeeIDs as $employeeID) {
                if (!(EventAttendance::query()->where('event_id', '=', $event->id)->where('employee_id', '=', $employeeID)->exists())) {
                    $eventAttendance = new EventAttendance();
                    $eventAttendance->event_id = $event->id;
                    $eventAttendance->employee_id = $employeeID;
                    $eventAttendance->save();
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function publish(Request $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();
            $employee = $user->employee;

            $approverEmployeeIDs = [];
            $approverEmployees = $this->approvalService->getApprovalUser($employee, ApprovalModule::ApprovalEvent);
            foreach ($approverEmployees as $approverEmployee) {
                $approverEmployeeIDs[] = $approverEmployee->employee_id;
            }


            /**
             * @var Event $event
             */
            $event = Event::query()
                ->where('requestor_employee_id', '=', $user->employee_id)
                ->where('last_status', '=', Event::StatusDraft)
                ->where('id', '=', $id)
                ->first();
            if (is_null($event)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Event is not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $event->last_status = Event::StatusPending;
            if ($event->event_type == Event::EventTypeNonAnggaran || count($approverEmployeeIDs) == 0) {
                $event->last_status = Event::StatusApprove;
            }

            $event->save();

            $eventHistory = new EventHistory();
            $eventHistory->event_id = $event->id;
            $eventHistory->employee_id = $user->employee_id;
            $eventHistory->status = $event->last_status;
            $eventHistory->save();

            if ($event->last_status == Event::StatusApprove) {
                $this->spreadEventDates($event);
            } else {
                foreach ($approverEmployeeIDs as $index => $approverEmployeeID) {
                    $eventApproval = new EventApproval();
                    $eventApproval->priority = $index;
                    $eventApproval->event_id = $event->id;
                    $eventApproval->employee_id = $approverEmployeeID;
                    $eventApproval->status = EventApproval::StatusPending;
                    $eventApproval->save();
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success!'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function eventApproval(EventApprovalRequest $request, int $id) {
        try {
            /**
             * @var Employee $employee
             */
            $employee = $request->user()->employee;

            /**
             * @var Event $event
             */
            $event = Event::query()
                ->where('id', '=', $id)
                ->where('last_status', '=', Event::StatusPending)
                ->first();
            if (!$event) {
                return response()->json([
                    'status' => false,
                    'message' => 'Event not found!'
                ], ResponseAlias::HTTP_NOT_FOUND);
            }

            /**
             * @var EventApproval $eventApproval
             */
            $eventApproval = EventApproval::query()
                ->where('event_id', '=', $event->id)
                ->where('employee_id', '=', $employee->id)
                ->where('status', '=', EventApproval::StatusPending)
                ->first();
            if (!$eventApproval) {
                return response()->json([
                    'status' => false,
                    'message' => 'You don\'t have access!'
                ], ResponseAlias::HTTP_NOT_FOUND);
            }

            if($eventApproval->priority > 0) {
                $isBeforeExist = $event->eventApprovals()
                    ->where('priority', '<', $eventApproval->priority)
                    ->where('status', '=', EventApproval::StatusPending)
                    ->exists();
                if ($isBeforeExist) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Last approver not doing approval yet!',
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            DB::beginTransaction();

            $eventApproval->status = $request->input('status');
            $eventApproval->notes = $request->input('notes');
            $eventApproval->save();

            if ($eventApproval->status == EventApproval::StatusRejected) {
                $event->last_status = Event::StatusReject;
                $event->save();

                /**
                 * @var EventApproval[] $nextApprovals
                 */
                $nextApprovals = $event->eventApprovals()
                    ->where('priority', '>', $eventApproval->priority)
                    ->get();
                foreach ($nextApprovals as $nextApproval) {
                    $nextApproval->status = EventApproval::StatusRejected;
                    $nextApproval->save();
                }
            } else if ($eventApproval->status == EventApproval::StatusApproved) {
                $isNextExist = $event->eventApprovals()
                    ->where('priority', '>', $eventApproval->priority)
                    ->exists();
                if (!$isNextExist) {
                    $event->last_status = Event::StatusApprove;
                    $event->save();

                    $this->spreadEventDates($event);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => 'Success'
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployeeEvents(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $unitRelationID = $request->get('unit_relation_id');

        $employeeEvents = EmployeeEvent::query()->with(['employee:employees.id,name', 'event:events.id,title,requestor_employee_id,timezone', 'event.requestorEmployee:employees.id,name']);
        $employeeEvents->join('employees', 'employees.id', '=', 'employee_events.employee_id');

        if ($this->isRequestedRoleLevel(Role::RoleSuperAdministrator)) {

        } else if ($this->isRequestedRoleLevel(Role::RoleAdmin)) {
            if (!$unitRelationID) {
                $defaultUnitRelationID = $user->employee->unit_id;

                if ($requestUnitRelationID = $this->getRequestedUnitID()) {
                    $defaultUnitRelationID = $requestUnitRelationID;
                }

                $unitRelationID = $defaultUnitRelationID;
            }
        } else {
            $employeeEvents->where('employees.id', '=', $user->employee_id);
        }

        if ($unitRelationID) {
            $employeeEvents->where(function(Builder $builder) use ($unitRelationID) {
                $builder->orWhere(function(Builder $builder) use ($unitRelationID) {
                    $builder->orWhere('employees.outlet_id', '=', $unitRelationID)
                        ->orWhere('employees.cabang_id', '=', $unitRelationID)
                        ->orWhere('employees.area_id', '=', $unitRelationID)
                        ->orWhere('employees.kanwil_id', '=', $unitRelationID)
                        ->orWhere('employees.corporate_id', '=', $unitRelationID);
                });
            });
        }

        if ($filter = $request->input('data_range')) {
            if($filter == 'this_month') {
                $employeeEvents->whereRaw("TO_CHAR(employee_events.event_datetime::DATE, 'YYYY-mm') = TO_CHAR(CURRENT_DATE, 'YYYY-mm')");
            }
        }

        $employeeEvents->select(['employee_events.*']);
        $employeeEvents->groupBy('employee_events.id');
        $employeeEvents->orderBy('employee_events.event_datetime', 'ASC');

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $this->list($employeeEvents, $request)
        ]);
    }

    public function spreadEventDates(Event $event) {
        $eventAttendances = $event->eventAttendances;
        $eventDates = $event->eventDates;

        foreach ($eventAttendances as $eventAttendance) {
            foreach ($eventDates as $eventDate) {
                $employeeEvent = new EmployeeEvent();
                $employeeEvent->employee_id = $eventAttendance->employee_id;
                $employeeEvent->event_id = $event->id;
                $employeeEvent->is_need_absence = $eventDate->is_need_absence;
                $employeeEvent->event_datetime = $eventDate->event_datetime;

                $employeeEvent->save();
            }

            $this->getNotificationService()->createNotification(
                $eventAttendance->employee_id,
                $event->title,
                $event->getEventRepeatDescriptionAttribute(),
                "Event",
                EmployeeNotification::ReferenceEvent,
                $event->id
            )->withSendPushNotification()->send();
        }
    }

    public function checkIn(CheckInEventRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $dataLocation = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude')
            ];

            /**
             * @var EmployeeEvent $employeeEvent
             */
            $employeeEvent = EmployeeEvent::query()
                ->where('id', '=', $id)
                ->where('employee_id', '=', $user->employee_id)
                ->whereNull('check_in_time')
                ->where('is_need_absence', '=', true)
                ->first();
            if (!$employeeEvent) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no event need to checked in!',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $event = $employeeEvent->event;
            $employeeTimezone = getTimezoneV2($dataLocation['latitude'], $dataLocation['longitude']);
            $distance = calculateDistance($event->latitude, $event->longitude, $dataLocation['latitude'], $dataLocation['longitude']);

            $checkInType = EmployeeAttendance::TypeOnSite;

            DB::beginTransaction();

            $employeeEvent->check_in_lat = $dataLocation['latitude'];
            $employeeEvent->check_in_long = $dataLocation['longitude'];
            $employeeEvent->check_in_time = Carbon::now();
            $employeeEvent->check_in_timezone = $employeeTimezone;
            $employeeEvent->save();

            $checkIn = new EmployeeAttendance();
            $checkIn->employee_id = $user->employee_id;
            $checkIn->real_check_in = $employeeEvent->check_in_time;
            $checkIn->checkin_type = $checkInType;
            $checkIn->checkin_lat = $employeeEvent->check_in_lat;
            $checkIn->checkin_long = $employeeEvent->check_in_long;
            $checkIn->is_need_approval = $checkInType == EmployeeAttendance::TypeOffSite;
            $checkIn->attendance_types = EmployeeAttendance::AttendanceTypeEvent;
            $checkIn->checkin_real_radius = $distance;
            $checkIn->approved = !($checkInType == EmployeeAttendance::TypeOffSite);
            $checkIn->check_in_tz = $employeeTimezone;
            $checkIn->is_late = false;
            $checkIn->late_duration = 0;
            $checkIn->save();

            $employeeEvent->employee_attendance_id = $checkIn->id;
            $employeeEvent->save();

            DB::commit();

            $this->getNotificationService()->createNotification(
                $user->employee_id,
                'Check In Event',
                'Check In Event Telah Berhasil'
            )->withSendPushNotification()->silent()->send();

            return response()->json([
                'status' => true,
                'message' => 'Success check in',
                'data' => [],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkOut(CheckOutEventRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $dataLocation = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude')
            ];

            /**
             * @var EmployeeEvent $employeeEvent
             */
            $employeeEvent = EmployeeEvent::query()
                ->where('id', '=', $id)
                ->where('employee_id', '=', $user->employee_id)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->where('is_need_absence', '=', true)
                ->first();
            if (!$employeeEvent) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no event need to checked in!',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $event = $employeeEvent->event;
            $employeeTimezone = getTimezoneV2($dataLocation['latitude'], $dataLocation['longitude']);
            $distance = calculateDistance($event->latitude, $event->longitude, $dataLocation['latitude'], $dataLocation['longitude']);

            $checkOutType = EmployeeAttendance::TypeOnSite;

            $checkInData = $employeeEvent->employeeAttendance;

            DB::beginTransaction();

            $employeeEvent->check_out_lat = $dataLocation['latitude'];
            $employeeEvent->check_out_long = $dataLocation['longitude'];
            $employeeEvent->check_out_time = Carbon::now();
            $employeeEvent->check_out_timezone = $employeeTimezone;
            $employeeEvent->save();

            if (!is_null($checkInData)) {
                $checkInData->real_check_out = $employeeEvent->check_out_time;
                $checkInData->checkout_lat = $employeeEvent->check_out_lat;
                $checkInData->checkout_long = $employeeEvent->check_out_long;
                $checkInData->checkout_real_radius = $distance;
                $checkInData->checkout_type = $checkOutType;
                $checkInData->check_out_tz = $employeeEvent->check_out_timezone;
                $checkInData->save();
            }

            DB::commit();

            $this->getNotificationService()->createNotification(
                $user->employee_id,
                'Check Out Event',
                'Check Out Event Telah Berhasil'
            )->withSendPushNotification()->silent()->send();

            return response()->json([
                'status' => true,
                'message' => 'Success check out',
                'data' => [],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getActiveEventEmployee(Request $request, int $id) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $employeeEvent = EmployeeEvent::query()
            ->with(['employee', 'event'])
            ->join('events', 'events.id', '=', 'employee_events.event_id')
            ->where('employee_events.employee_id', '=', $user->employee_id)
            ->whereIn('events.last_status', [Event::StatusApprove])
            ->whereRaw("TO_CHAR(employee_events.event_datetime::DATE, 'YYYY-mm-dd') = ?", Carbon::now()->format('Y-m-d'))
            ->where('event_id', '=', $id)
            ->select(['employee_events.*'])
            ->orderBy('employee_events.event_datetime', 'ASC')
            ->first();
        if (!$employeeEvent) {
            return response()->json([
                'status' => false,
                'message' => 'There is no event need to checked in!',
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success!',
            'data' => $employeeEvent
        ]);
    }

    /**
     * @param Event $event
     * @return EventDate[]
     */
    private function generateEventDate(Event $event): array {
        $eventDates = [];

        $parsedEventDate = Carbon::parse($event->date_event);

        if ($event->is_repeat) {
            $endDate = Carbon::parse($event->repeat_end_date);

            if ($event->repeat_type == Event::RepeatTypeDaily) {
                $eventDate = new EventDate();
                $eventDate->is_need_absence = $event->is_need_absence;
                $eventDate->event_date = $parsedEventDate->format('Y-m-d');
                $eventDate->event_time = $event->time_event;

                $eventDates[] = $eventDate;

                while(true) {
                    $eventDate = new EventDate();
                    $eventDate->is_need_absence = $event->is_need_absence;
                    $eventDate->event_date = $parsedEventDate->addDays($event->repeat_every)->format('Y-m-d');
                    $eventDate->event_time = $event->time_event;

                    if ($parsedEventDate->isAfter($endDate)) {
                        break;
                    }

                    $eventDates[] = $eventDate;
                }
            } else if ($event->repeat_type == Event::RepeatTypeYearly) {
                $eventDate = new EventDate();
                $eventDate->is_need_absence = $event->is_need_absence;
                $eventDate->event_date = $parsedEventDate->format('Y-m-d');
                $eventDate->event_time = $event->time_event;

                $eventDates[] = $eventDate;

                while(true) {
                    $eventDate = new EventDate();
                    $eventDate->is_need_absence = $event->is_need_absence;
                    $eventDate->event_date = $parsedEventDate->addYears($event->repeat_every)->format('Y-m-d');
                    $eventDate->event_time = $event->time_event;

                    if ($parsedEventDate->isAfter($endDate)) {
                        break;
                    }

                    $eventDates[] = $eventDate;
                }
            } else if ($event->repeat_type == Event::RepeatTypeWeekly) {
                $dataDatePivot = [];

                foreach (explode(",", $event->repeat_days) as $day) {
                    $pivotData = Carbon::parse($parsedEventDate->format('Y-m-d'));
                    $pivotData->addDays(Event::DaysOfWeekMap[$day] - $pivotData->dayOfWeek);

                    $dataDatePivot[] = $pivotData;
                }

                for ($i = 0; $i < count($dataDatePivot); $i++) {
                    $eventDate = new EventDate();
                    $eventDate->is_need_absence = $event->is_need_absence;
                    $eventDate->event_date = $dataDatePivot[$i]->format('D, Y-m-d');
                    $eventDate->event_time = $event->time_event;

                    if ($dataDatePivot[$i]->isAfter($endDate) || $dataDatePivot[$i]->isBefore($parsedEventDate)) {
                        continue;
                    }

                    $eventDates[] = $eventDate;
                }

                while (true) {
                    $totalBreak = 0;

                    for ($i = 0; $i < count($dataDatePivot); $i++) {
                        $eventDate = new EventDate();
                        $eventDate->is_need_absence = $event->is_need_absence;
                        $eventDate->event_date = $dataDatePivot[$i]->addWeeks($event->repeat_every)->format('Y-m-d');
                        $eventDate->event_time = $event->time_event;

                        if ($dataDatePivot[$i]->isAfter($endDate) || $dataDatePivot[$i]->isBefore($parsedEventDate)) {
                            $totalBreak++;
                            continue;
                        }

                        $eventDates[] = $eventDate;
                    }

                    if($totalBreak >= count($dataDatePivot)) {
                        break;
                    }
                }
            } else if ($event->repeat_type == Event::RepeatTypeMonthly) {
                $repeatDays = explode(",", $event->repeat_days);

                if (count($repeatDays) == 2) {
                    $pivotDate = Carbon::parse(sprintf("first day of %s %s", $parsedEventDate->monthName, $parsedEventDate->year));

                    while (true) {
                        $itemDate = Carbon::parse(sprintf("%s %s of %s %s", $repeatDays[0], $repeatDays[1], $pivotDate->monthName, $pivotDate->year));

                        $eventDate = new EventDate();
                        $eventDate->is_need_absence = $event->is_need_absence;
                        $eventDate->event_date = $itemDate->format('Y-m-d');
                        $eventDate->event_time = $event->time_event;

                        $pivotDate->addMonth();

                        if ($itemDate->isBefore($parsedEventDate)) {
                            continue;
                        }
                        if ($itemDate->isAfter($endDate)) {
                            break;
                        }

                        $eventDates[] = $eventDate;
                    }
                } else {
                    $i = 0;
                    while (true) {
                        $itemDate = Carbon::parse($parsedEventDate->format('Y-m-d'))->addMonths($i);

                        $eventDate = new EventDate();
                        $eventDate->is_need_absence = $event->is_need_absence;
                        $eventDate->event_date = $itemDate->format('Y-m-d');
                        $eventDate->event_time = $event->time_event;

                        if ($itemDate->isAfter($endDate)) {
                            break;
                        }

                        $i++;
                        $eventDates[] = $eventDate;
                    }
                }
            }
        } else {
            $eventDate = new EventDate();
            $eventDate->is_need_absence = $event->is_need_absence;
            $eventDate->event_date = $parsedEventDate->format('D, Y-m-d');
            $eventDate->event_time = $event->time_event;

            $eventDates[] = $eventDate;
        }

        return $eventDates;
    }

    public function monthlyEvaluate(Request  $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $query = EmployeeEvent::query()->with(['employee:employees.id,name', 'event:events.id,title,requestor_employee_id,timezone', 'event.requestorEmployee:employees.id,name', 'employeeAttendance'])
                ->where('employee_events.employee_id', '=', $user->employee_id)
                ->orderBy('employee_events.event_datetime', 'ASC');

            if ($monthly = $request->query('monthly')) {
                $query->whereRaw("TO_CHAR(employee_events.event_datetime, 'YYYY-mm') = ?", [$monthly]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Succcess fetch data',
                'data' => [
                    'meta' => [
                        'full_attendance' => (clone $query)->whereNotNull('employee_events.check_in_time')->count(),
                        'late_check_in' => (clone $query)->whereRaw('employee_events.check_in_time > employee_events.event_datetime')->count(),
                        'not_check_out' => (clone $query)->whereNull('employee_events.check_out_time')->count(),
                        'early_check_out' => 0,
                        'not_attendance' => (clone $query)->whereNull('employee_events.check_in_time')->whereNull('employee_events.check_out_time')->count(),
                        'total_schedule' => (clone $query)->count()
                    ],
                    'data' => $this->list($query, $request)
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
