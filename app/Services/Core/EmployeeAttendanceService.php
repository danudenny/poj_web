<?php

namespace App\Services\Core;

use App\Helpers\UnitHelper;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceHistory;
use App\Models\LateCheckin;
use App\Models\User;
use App\Models\WorkReporting;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EmployeeAttendanceService extends BaseService {


    private EmployeeTimesheetService $employeeTimesheetService;

    public function __construct(EmployeeTimesheetService $employeeTimesheetService) {
        $this->employeeTimesheetService = $employeeTimesheetService;
    }
    public function index(Request $request): JsonResponse
    {
        $auth = Auth::user();
        $roles = Auth::user()->roles;
        try {
            $attendances = EmployeeAttendance::query();
            $attendancesData = [];

            $attendances->with(['employee', 'employee.kanwil', 'employee.area', 'employee.cabang', 'employee.outlet', 'employee.employeeDetail', 'employee.employeeDetail.employeeTimesheet', 'employeeAttendanceHistory']);
            $attendances->when($request->name, function ($query) use ($request) {
                $query->whereHas('employee', function (Builder $query) use ($request) {
                    $query->whereRaw('LOWER(name) LIKE ?', [strtolower('%' . request()->query('name') . '%')]);
                });
            });

            $highestPriorityRole = null;
            $highestPriority = null;

            foreach ($roles as $role) {
                if ($highestPriority === null || $role['priority'] < $highestPriority) {
                    $highestPriorityRole = $role;
                    $highestPriority = $role['priority'];
                }
            }

            if ($highestPriorityRole->role_level === 'superadmin') {
                $attendancesData = $attendances->paginate($request->get('limit', 10));
            } else if ($highestPriorityRole->role_level === 'staff') {
                $attendancesData = $attendances->where('employee_id', Auth::user()->employee_id)
                    ->paginate($request->get('limit', 10));
            } else if ($highestPriorityRole->role_level === 'admin') {
                $empUnit = $auth->employee->getRelatedUnit();
                $lastUnit = $auth->employee->getLastUnit();
                $empUnit[] = $lastUnit;
                $flatUnit = UnitHelper::flattenUnits($empUnit);
                $relationIds = array_column($flatUnit, 'relation_id');
                $attendancesData = $attendances->whereHas('employee', function (Builder $query) use ($relationIds) {
                     $query->whereIn('kanwil_id', $relationIds)
                         ->orWhereIn('area_id', $relationIds)
                         ->orWhereIn('cabang_id', $relationIds)
                         ->orWhereIn('outlet_id', $relationIds);
                })->paginate(10);

            } else {
                $attendancesData = $attendances->where('employee_id', Auth::user()->employee_id)
                    ->paginate($request->get('limit', 10));
            }

            return response()->json([
                'status' => true,
                'message' => 'Success get data!',
                'data' => $attendancesData
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => self::SOMETHING_WRONG.' : '.$e->getMessage()
            ], 500);
        }
    }

    public function view(Request $request, int $id) {
        $attendance = EmployeeAttendance::query()->with(['employeeAttendanceHistory', 'employee'])->where('id', '=', $id)->first();

        if (!$attendance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee attendance not found!'
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success get data!',
            'data' => $attendance
        ]);
    }

    function getLastUnit($data) {
        $lastData = null;
        foreach ($data as $item) {
            if ($item === null) {
                break;
            }
            $lastData = $item;
        }
        return $lastData;
    }
    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function checkIn($request): JsonResponse
    {
        $empData = auth()->user()->employee;
        $filteredUnitData = [$empData->kanwil,$empData->area,$empData->cabang,$empData->outlet];

        $timesheetSchedules = $empData->timesheetSchedules;
        if (!$timesheetSchedules) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee schedule not found!'
            ], 400);
        }

        $now = Carbon::now();
        $empSchedule = null;

        foreach ($timesheetSchedules as $schedule) {
            $day = $schedule['date'];
            $year = $schedule['period']['year'];
            $month = $schedule['period']['month'];
            $currentDate = Carbon::createFromDate($year, $month, $day);
            $carbonDate = $currentDate->format('Y-m-d');
            if ($carbonDate === $now->format('Y-m-d')) {
                $empSchedule = $schedule;
            }
        }

        if (!$empSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee schedule not found! Contact Administrator for Help!'
            ], 400);
        }

        $workLocation = \auth()->user()->employee->last_unit;

        // BEGIN : Check if time is in range
        $employeeTimeZone = getTimezone($request->lat, $request->long);
        if (!$workLocation->lat && !$workLocation->long) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work location not found!'
            ], 400);
        }
        $companyTimeZone = getTimezone(floatval($workLocation->lat), floatval($workLocation->long));

        $employeeTimesheetStartTime = Carbon::parse($empSchedule['timesheet']['start_time'], $companyTimeZone);
        $employeeTimesheetEndTime = Carbon::parse($empSchedule['timesheet']['end_time'], $companyTimeZone);
        if ($employeeTimesheetEndTime < $employeeTimesheetStartTime) {
            Carbon::parse($employeeTimesheetEndTime)->addDay();
        }
        $parseRequestedTime = Carbon::parse($now);
        $requestedTime = Carbon::createFromTimeString($parseRequestedTime);

        $adjustedStartTime = $employeeTimesheetStartTime->copy()->subMinutes($workLocation->early_buffer)->setTimezone($companyTimeZone);
        $adjustedEndTime = $employeeTimesheetEndTime->copy()->subMinutes($workLocation->late_buffer)->setTimezone($companyTimeZone);

        // END : Check if time is in range

        // BEGIN : Check if employee has checked in today
        $checkInData = $empData->attendances;

        foreach ($checkInData as $checkIn) {
            if ($checkIn->real_check_in) {
                if (Carbon::parse($checkIn->real_check_in)->format('Y-m-d') == Carbon::parse($parseRequestedTime)->format('Y-m-d')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You have checked in today!'
                    ], 400);
                }
            }
        }
        // END : Check if employee has checked in today

        // BEGIN : Calculate distance
        if ($workLocation->lat === null && $workLocation->long === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work location not found!'
            ], 400);
        }

        $distance = calculateDistance($request->lat, $request->long, floatval($workLocation->lat), floatval($workLocation->long));
        // END : Calculate distance

        $earlyTolerance = $workLocation->early_buffer;
        $lateTolerance = $workLocation->late_buffer;

        $employeeCheckInTime = Carbon::parse($now, $companyTimeZone);
        $companyCheckInTime = Carbon::createFromFormat('H:i', $empSchedule['timesheet']['start_time'], $companyTimeZone);

        $earlyBoundary = $companyCheckInTime->copy()->subMinutes($earlyTolerance);
        $lateBoundary = $companyCheckInTime->copy()->addMinutes($lateTolerance);

        $lateDifference = $lateBoundary->diffInMinutes($employeeCheckInTime);
        $isOnTime = $employeeCheckInTime->between($earlyBoundary, $lateBoundary);

        $isNeedApproval = false;
        if ($distance <= intval($workLocation->radius)){
            $attType = "onsite";
        } else {
            $attType = "offsite";
            $isNeedApproval = true;
        }

        DB::beginTransaction();
        try {
            $checkIn = new EmployeeAttendance();
            $checkIn->employee_id = auth()->user()->employee_id;
            $checkIn->real_check_in = Carbon::now($companyTimeZone)->toDateTimeString();
            $checkIn->checkin_type = $attType;
            $checkIn->checkin_lat = $request->lat;
            $checkIn->checkin_long = $request->long;
            $checkIn->is_need_approval = $isNeedApproval;
            $checkIn->attendance_types = $request->attendance_types;
            $checkIn->checkin_real_radius = $distance;
            $checkIn->approved = !$isNeedApproval;
            $checkIn->check_in_tz = $employeeTimeZone;
            $checkIn->is_late = $lateDifference > 0;
            $checkIn->late_duration = $lateDifference;

            if (!$checkIn->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save data!'
                ], 400);
            }

            $attHistory = new EmployeeAttendanceHistory();
            $attHistory->employee_id = auth()->user()->employee_id;
            $attHistory->employee_attendances_id = $checkIn->id;
            $attHistory->status = !$isNeedApproval ? 'approved' : 'pending';

            if (!$attHistory->save()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save data!'
                ], 400);
            }

            $columnName = null;
            if ($lateDifference >= 15) {
                if ($lateDifference <= 15) {
                    $columnName = 'late_15';
                } else if ($lateDifference <= 30) {
                    $columnName = 'late_30';
                } else if ($lateDifference <= 45) {
                    $columnName = 'late_45';
                } else if ($lateDifference <= 60) {
                    $columnName = 'late_60';
                } else if ($lateDifference <= 75) {
                    $columnName = 'late_75';
                } else if ($lateDifference <= 90) {
                    $columnName = 'late_90';
                } else if ($lateDifference <= 105) {
                    $columnName = 'late_105';
                } else if ($lateDifference <= 120) {
                    $columnName = 'late_120';
                } else {
                    $columnName = 'late_120';
                }
                $year = $employeeCheckInTime->year;
                $month = $employeeCheckInTime->month;

                if ($columnName) {
                    $totalLateColumn = 'total_' . $columnName;
                    $lateCheckin = LateCheckin::where('employee_id', auth()->user()->employee_id)
                        ->where('year', $year)
                        ->where('month', $month)
                        ->first();

                    if (!$lateCheckin) {
                        $lateCheckin = new LateCheckin();
                        $lateCheckin->employee_id = auth()->user()->employee_id;
                        $lateCheckin->month = $month;
                        $lateCheckin->year = $year;
                        $lateCheckin->$columnName = 1;
                        $lateCheckin->$totalLateColumn = $lateDifference;
                        $lateCheckin->save();
                    }

                    $lateCheckin->$columnName++;
                    $lateCheckin->$totalLateColumn = $lateCheckin->$totalLateColumn + $lateDifference;
                    $lateCheckin->save();
                }
            }

            $getUser = User::where('employee_id', auth()->user()->employee_id)->first();
            $getUser->is_normal_checkin = true;
            $getUser->is_normal_checkout = false;
            $getUser->save();

            if ($isNeedApproval) {
                $getApproval = Approval::with(['users', 'approvalModule'])
                    ->whereHas('approvalModule', function ($query) {
                        $query->where('name', 'Attendance');
                    });
            }

            DB::commit();

            $notification = [
                'title' => 'Attendance Check In',
                'body' => 'You have successfully checked in!'
            ];

            $recipients = User::where('employee_id', auth()->user()->employee_id)->pluck('fcm_token')->toArray();

            fcm()
                ->to($recipients)
                ->priority('high')
                ->timeToLive(0)
                ->notification($notification)
                ->enableResponseLog()
                ->send();

            return response()->json([
                'status' => true,
                'message' => 'Success check in!',
                'data' => $checkIn
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @throws GuzzleException
     */
    public function checkOut($request): JsonResponse
    {
        $empData = auth()->user()->employee;

        $filteredUnitData = [$empData->kanwil,$empData->area,$empData->cabang,$empData->outlet];
        $timesheetSchedules = $empData->timesheetSchedules;

        if (!$timesheetSchedules) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee schedule not found!'
            ], 400);
        }

        $now = Carbon::now();
        $empSchedule = null;

        foreach ($timesheetSchedules as $schedule) {
            $day = $schedule['date'];
            $year = $schedule['period']['year'];
            $month = $schedule['period']['month'];
            $carbonDate = Carbon::create($year, $month, $day)->format('Y-m-d');

            if ($carbonDate === $now->format('Y-m-d')) {
                $empSchedule = $schedule;
            }
        }

        if (!$empSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee schedule not found! Contact Administrator for Help!'
            ], 400);
        }

        $workLocation = $this->getLastUnit($filteredUnitData);

        // BEGIN : Check if time is in range
        $employeeTimeZone = getTimezone($request->lat, $request->long);
        $companyTimeZone = getTimezone(floatval($workLocation->lat), floatval($workLocation->long));

        $employeeTimesheetStartTime = Carbon::parse($empSchedule['timesheet']['start_time'], $companyTimeZone);
        $employeeTimesheetEndTime = Carbon::parse($empSchedule['timesheet']['end_time'], $companyTimeZone);
        if ($employeeTimesheetEndTime < $employeeTimesheetStartTime) {
            Carbon::parse($employeeTimesheetEndTime)->addDay();
        }
        $parseRequestedTime = Carbon::parse($now);
        $requestedTime = Carbon::createFromTimeString($parseRequestedTime);
        // END : Check if time is in range

        //check if employee has submit worklocation today
        $workReporting = WorkReporting::where('employee_id', $empData->id)
            ->whereDate('date', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        if (!$workReporting) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have not submit work reporting today!'
            ], 400);
        }

        $employeeTimeZone = getTimezone($request->lat, $request->long);
        $companyTimeZone = getTimezone($workLocation['lat'], $workLocation['long']);
        $checkInData = $empData->attendances->first();

        if (!auth()->user()->is_normal_checkin) {
            return response()->json([
                'message' => 'The employee has not checked in today.'
            ], 400);
        }

        if ($workLocation['lat'] === null && $workLocation['long'] === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work location not found!'
            ], 400);
        }

        $distance = calculateDistance(
            $request->lat, $request->long,
            floatval($workLocation['lat']),
            floatval($workLocation['long'])
        );

        DB::beginTransaction();
        try {
            $checkInData->real_check_out = Carbon::now($companyTimeZone)->toDateTimeString();
            $checkInData->checkout_lat = $request->lat;
            $checkInData->checkout_long = $request->long;
            $checkInData->checkout_real_radius = $distance;
            $checkInData->check_out_tz = $employeeTimeZone;
            $checkInData->is_early = Carbon::parse(now(), $companyTimeZone)->lessThan(Carbon::parse($empSchedule['timesheet']['end_time'], $companyTimeZone));
            if ($requestedTime->lessThan($employeeTimesheetEndTime)) {
                $checkInData->early_duration = $requestedTime->diffInMinutes($employeeTimesheetEndTime);
            } else {
                $checkInData->early_duration = 0;
            }

            $checkInData->duration = Carbon::parse($checkInData->real_check_in, $companyTimeZone)->diffInMinutes(Carbon::parse($checkInData->real_check_out, $companyTimeZone));
            if (!$checkInData->save()) {
                throw new Exception('Failed save checkout data!');
            }

            $attHistory = new EmployeeAttendanceHistory();
            $attHistory->employee_id = auth()->user()->employee_id;
            $attHistory->employee_attendances_id = $checkInData->id;
            $attHistory->status = $checkInData->approved ? 'approved' : 'pending';

            if (!$attHistory->save()) {
                throw new Exception('Failed save attendance histories data!');
            }

            $getUser = User::where('employee_id', auth()->user()->employee_id)->first();
            $getUser->is_normal_checkin = false;
            $getUser->is_normal_checkout = true;
            $getUser->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success save data!',
                'data' => $checkInData
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed save data!',
                'data' => $e->getMessage()
            ]);
        }
    }

    public function approve($request, $id): JsonResponse
    {
        $items = [];
        $user = \auth()->user();
        $lastUnit = $user->employee->last_unit;
        $findApproval = Approval::where('unit_id', $lastUnit->id)->get();
        $findApproval->map(function ($item) {
            $item->users = $item->users->map(function ($user) {
                return [
                    'id' => $user->user->id,
                    'name' => $user->user->name,
                    'email' => $user->user->email,
                    'fcm_token' => $user->user->fcm_token,
                ];
            });
            return $item;
        });

        $getAttendance = EmployeeAttendance::where('id', $id)->where('is_need_approval', true)->first();
        if (!$getAttendance) {
            return response()->json([
                'status' => false,
                'message' => 'Data not found!'
            ]);
        }

        DB::beginTransaction();
        try {
            $getAttendance->is_need_approval = false;
            $getAttendance->approved = true;
            if (!$getAttendance->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Failed save data!'
                ]);
            }

            $getHistory = EmployeeAttendanceHistory::where('employee_attendances_id', $id)->first();
            $getHistory->status = 'approved';

            if (!$getHistory->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Failed save data hostory!'
                ]);
            }
            $getUser = $getHistory->employee->user;

            fcm()
                ->to([$getUser->fcm_token])
                ->priority('high')
                ->timeToLive(0)
                ->notification([
                    'title' => 'Approval',
                    'body' => 'Your attendance has been approved!',
                ])
                ->enableResponseLog()
                ->send();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Success save data!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getActiveAttendance(Request $request) {
        try {
            /**
             * @var Employee $employee
             */
            $employee = $request->user()->employee;

            $metaData = [
                'employee_id' => $employee->id,
                'system_current_time' => Carbon::now()->format('Y-m-d H:i:s'),
                'current_time_with_timezone' => null,
                'timezone' => null,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ];

            if ($metaData['latitude'] == null || $metaData['longitude'] == null) {
                return response()->json([
                    'status' => false,
                    'message' => "Invalid Location"
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $timezone = getTimezoneV2($metaData['latitude'], $metaData['longitude']);

            $metaData['current_time_with_timezone'] = Carbon::now()->setTimezone($timezone)->format('Y-m-d H:i:s');
            $metaData['timezone'] = $timezone;

            $activeSchedule = [
                'attendance' => [
                    'normal' => null,
                    'event' => null,
                    'overtime' => null,
                    'backup' => null,
                ],
                'meta_data' => $metaData
            ];

            if ($event = $employee->getActiveEvent()) {
                $activeSchedule['attendance']['event'] = [
                    'start_time' => Carbon::parse($event->event_datetime)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'check_in_time' => $event->check_in_time ? Carbon::parse($event->check_in_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'check_out_time' => $event->check_out_time ? Carbon::parse($event->check_out_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'reference_id' => $event->id
                ];
            }

            if ($overtime = $employee->getActiveOvertime()) {
                $activeSchedule['attendance']['overtime'] = [
                    'start_time' => Carbon::parse($overtime->overtimeDate->start_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'end_time' => Carbon::parse($overtime->overtimeDate->end_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'check_in_time' => $overtime->check_in_time ? Carbon::parse($overtime->check_in_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'check_out_time' => $overtime->check_out_time ? Carbon::parse($overtime->check_out_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'reference_id' => $overtime->id
                ];
            }

            if ($backup = $employee->getActiveBackup()) {
                $activeSchedule['attendance']['backup'] = [
                    'start_time' => Carbon::parse($backup->backupTime->start_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'end_time' => Carbon::parse($backup->backupTime->end_time)->setTimezone($timezone)->format('Y-m-d H:i:s'),
                    'check_in_time' => $backup->check_in_time ? Carbon::parse($backup->check_in_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'check_out_time' => $backup->check_out_time ? Carbon::parse($backup->check_out_time)->setTimezone($timezone)->format('Y-m-d H:i:s') : null,
                    'reference_id' => $backup->id
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => $activeSchedule
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
