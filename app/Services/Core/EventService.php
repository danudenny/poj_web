<?php

namespace App\Services\Core;

use App\Http\Requests\Event\CheckInEventRequest;
use App\Http\Requests\Event\CheckOutEventRequest;
use App\Http\Requests\Event\CreateEventRequest;
use App\Http\Requests\Event\EventApprovalRequest;
use App\Models\ApprovalModule;
use App\Models\ApprovalUser;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeEvent;
use App\Models\EmployeeNotification;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventDate;
use App\Models\EventHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EventService extends BaseService
{
    public function index(Request $request) {
        $query = Event::query();
        $query->orderBy('id', 'DESC');

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
            'eventAttendances', 'eventAttendances.employee:employees.id,name',
            'eventHistories', 'eventHistories.employee:employees.id,name'
        ])->find($id);
        if (is_null($event)) {
            return response()->json([
                'status' => false,
                'message' => 'Event is not found!'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $event
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
            $event->last_status = Event::StatusPending;
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

            if ($event->event_type == Event::EventTypeNonAnggaran) {
                $event->last_status = Event::StatusApprove;
            }

            /**
             * @var int[] $eventAttendances
             */
            $eventAttendances = $request->input('event_attendances', []);

            if ($event->is_repeat && $event->repeat_end_date == '') {
                return response()->json([
                    'status' => false,
                    'message' => 'End date is required!'
                ], Response::HTTP_BAD_REQUEST);
            }
            if ($event->is_repeat && (Carbon::parse($event->repeat_end_date)->lessThan(Carbon::parse($event->date_event)))) {
                return response()->json([
                    'status' => false,
                    'message' => 'End date cannot less than event date!'
                ], Response::HTTP_BAD_REQUEST);
            }
            if ($event->is_repeat && (Carbon::parse($event->repeat_end_date)->lessThan(Carbon::today()))) {
                return response()->json([
                    'status' => false,
                    'message' => 'End date cannot less than today!'
                ], Response::HTTP_BAD_REQUEST);
            }

            $eventDates = $this->generateEventDate($event);
            if (count($eventDates) == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'There is no event dates!'
                ], Response::HTTP_BAD_REQUEST);
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

            if ($event->last_status == Event::StatusApprove) {
                $this->spreadEventDates($event);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $event
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function eventApproval(EventApprovalRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var ApprovalUser[] $approvalUsers
             */
            $approvalUsers = ApprovalUser::query()
                ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
                ->join('approval_modules', 'approvals.approval_module_id', '=', 'approval_modules.id')
                ->where('approval_modules.name', '=', ApprovalModule::ApprovalEvent)
                ->where('approvals.unit_id', '=', $user->employee->getLastUnitID())
                ->where('approvals.is_active', '=', true)
                ->orderBy('approval_users.id', 'ASC')
                ->get(['approval_users.*']);

            $isValidToCreate = false;
            foreach ($approvalUsers as $approvalUser) {
                if ($approvalUser->user_id == $user->id) {
                    $isValidToCreate = true;
                }
            }

            if (!$isValidToCreate) {
                return response()->json([
                    'message' => 'You don\'t have access to do approval!'
                ], Response::HTTP_BAD_REQUEST);
            }

            /**
             * @var Event $event
             */
            $event = Event::query()->find($id);
            if (is_null($event)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Event is not found!'
                ], Response::HTTP_BAD_REQUEST);
            }

            /**
             * @var EventHistory[] $eventHistories
             */
            $eventHistories = EventHistory::query()
                ->where('event_id', '=', $event->id)
                ->where('status', '!=', Event::StatusPending)->get();
            if (count($eventHistories) >= count($approvalUsers)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Event already approved!'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($approvalUsers[count($eventHistories)]->user_id != $user->id) {
                return response()->json([
                    'message' => 'Last approver not doing approval!'
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $event->last_status = $request->input('status');
            $event->save();

            $eventHistory = new EventHistory();
            $eventHistory->event_id = $event->id;
            $eventHistory->employee_id = $user->employee_id;
            $eventHistory->status = $event->last_status;
            $eventHistory->notes = $request->input('notes');
            $eventHistory->save();

            $eventHistories[] = $eventHistory;

            if (count($eventHistories) >= count($approvalUsers)) {
                $this->spreadEventDates($event);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => 'Success'
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployeeEvents(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $employeeEvents = EmployeeEvent::query()->with(['employee:employees.id,name', 'event:events.id,title,requestor_employee_id', 'event.requestorEmployee:employees.id,name'])
            ->whereRaw("TO_CHAR(event_datetime::DATE, 'YYYY-mm') = TO_CHAR(CURRENT_DATE, 'YYYY-mm')")
            ->orderBy('id', 'DESC');

        if (!$user->inRoleLevel([Role::RoleSuperAdministrator])) {
            $employeeEvents->where('employee_id', '=', $user->employee_id);
        }

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
            );
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

            /**
             * @var EmployeeAttendance $checkInData
             */
            $checkInData = $user->employee->attendances()
                ->where('attendance_types', '=', EmployeeAttendance::AttendanceTypeEvent)
                ->orderBy('id', 'DESC')
                ->first();

            DB::beginTransaction();

            $employeeEvent->check_out_lat = $dataLocation['latitude'];
            $employeeEvent->check_out_long = $dataLocation['longitude'];
            $employeeEvent->check_out_time = Carbon::now();
            $employeeEvent->check_out_timezone = $employeeTimezone;
            $employeeEvent->save();

            if ($checkInData) {
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
}
