<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property string $last_status
 * @property int $requestor_employee_id
 * @property string $event_type
 * @property string $image_url
 * @property string $title
 * @property string $description
 * @property float $latitude
 * @property float $longitude
 * @property string $location_type
 * @property string $address
 * @property string $date_event
 * @property string $time_event
 * @property bool $is_need_absence
 * @property bool $is_repeat
 * @property string|null $repeat_type
 * @property int|null $repeat_every
 * @property string|null $repeat_days
 * @property string|null $repeat_end_date
 * @property string $timezone
 *
 * Relations:
 * @property-read Employee $requestorEmployee
 * @property-read EventDate[] $eventDates
 * @property-read EventAttendance[] $eventAttendances
 * @property-read EventHistory[] $eventHistories
 */
class Event extends Model
{
    use HasFactory;

    const EventTypeAnggaran = "anggaran";
    const EventTypeNonAnggaran = "non-anggaran";

    const LocationTypeInternal = "internal";
    const LocationTypeExternal = "external";

    const StatusDraft = "draft";
    const StatusPending = "pending";
    const StatusApprove = "approve";
    const StatusReject = "reject";

    const RepeatTypeDaily = "daily";
    const RepeatTypeWeekly = "weekly";
    const RepeatTypeMonthly = "monthly";
    const RepeatTypeYearly = "yearly";

    const DaysOfWeekMap = [
        "sunday" => 0,
        "monday" => 1,
        "tuesday" => 2,
        "wednesday" => 3,
        "thursday" => 4,
        "friday" => 5,
        "saturday" => 6
    ];

    protected $appends = [
        'event_repeat_description',
        'is_can_approve'
    ];

    public function getEventRepeatDescriptionAttribute() {
        if ($this->is_repeat) {
            switch ($this->repeat_type) {
                case self::RepeatTypeDaily:
                    return sprintf("Repeat every %s days from %s until %s", $this->repeat_every, Carbon::parse($this->date_event)->format('D, d M Y'), Carbon::parse($this->repeat_end_date)->format('D, d M Y'));
                case self::RepeatTypeWeekly:
                    return sprintf("Repeat on %s every %s weeks from %s until %s", $this->repeat_days, $this->repeat_every, Carbon::parse($this->date_event)->format('D, d M Y'), Carbon::parse($this->repeat_end_date)->format('D, d M Y'));
                case self::RepeatTypeMonthly:
                    $repeatDays = explode(",", $this->repeat_days);
                    if (count($repeatDays) == 2) {
                        return sprintf("Repeat on %s %s every %s months from %s until %s", $repeatDays[0], $repeatDays[1], $this->repeat_every, Carbon::parse($this->date_event)->format('D, d M Y'), Carbon::parse($this->repeat_end_date)->format('D, d M Y'));
                    } else {
                        return sprintf("Repeat on %s of months every %s months from %s until %s", Carbon::parse($this->date_event)->format('d'), $this->repeat_every, Carbon::parse($this->date_event)->format('D, d M Y'), Carbon::parse($this->repeat_end_date)->format('D, d M Y'));
                    }
                case self::RepeatTypeYearly:
                    return sprintf("Repeat on %s every %s years from %s until %s", Carbon::parse($this->date_event)->format('d M'), $this->repeat_every, Carbon::parse($this->date_event)->format('D, d M Y'), Carbon::parse($this->repeat_end_date)->format('D, d M Y'));
            }
        }

        return sprintf("On %s at %s", Carbon::parse($this->date_event)->format('D, d M Y'), $this->time_event);
    }

    public function getIsCanApproveAttribute() {
        if ($this->event_type == self::EventTypeNonAnggaran) {
            return false;
        }

        $approvalUsers = ApprovalUser::query()
            ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
            ->join('approval_modules', 'approvals.approval_module_id', '=', 'approval_modules.id')
            ->where('approval_modules.name', '=', ApprovalModule::ApprovalEvent)
            ->where('approvals.unit_id', '=', $this->requestorEmployee->unit_id)
            ->where('approvals.is_active', '=', true)
            ->orderBy('approval_users.id', 'ASC')
            ->get(['approval_users.*']);

        $eventHistories = EventHistory::query()
            ->where('event_id', '=', $this->id)
            ->where('status', '!=', Event::StatusPending)->get();

        if (count($eventHistories) >= count($approvalUsers)) {
            return false;
        }

        return true;
    }

    /**
     * @return HasMany
     */
    public function eventDates() {
        return $this->hasMany(EventDate::class, 'event_id');
    }

    /**
     * @return HasMany
     */
    public function eventAttendances() {
        return $this->hasMany(EventAttendance::class, 'event_id');
    }

    public function eventHistories() {
        return $this->hasMany(EventHistory::class, 'event_id')->orderBy('id', 'ASC');
    }

    public function requestorEmployee() {
        return $this->belongsTo(Employee::class, 'requestor_employee_id');
    }
}
