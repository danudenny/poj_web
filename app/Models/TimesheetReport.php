<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property string $start_date
 * @property string $end_date
 * @property string $unit_relation_id
 * @property string $last_sync
 * @property string $last_sync_by
 * @property string $last_sent_at
 * @property string $last_sent_by
 * @property string $status
 * @property string $created_by
 *
 * Relations:
 * @property-read Unit  $unit
 * @property-read TimesheetReportDetail[] $timesheetReportDetails
 */
class TimesheetReport extends Model
{
    use HasFactory;

    const StatusPending = "pending";
    const StatusSuccess = "sent";

    protected $with = [
        'unit'
    ];

    protected $appends = [
        'last_sync_with_client_timezone',
        'last_sent_with_client_timezone',
        'total_success',
        'total_failed',
        'total_pending'
    ];

    public function getLastSyncWithClientTimezoneAttribute() {
        $lastSync = Carbon::parse($this->last_sync, 'UTC');

        return $lastSync->setTimezone(getClientTimezone())->format('Y-m-d H:i:s');
    }

    public function getLastSentWithClientTimezoneAttribute() {
        if ($this->last_sent_at) {
            $lastSync = Carbon::parse($this->last_sync, 'UTC');

            return $lastSync->setTimezone(getClientTimezone())->format('Y-m-d H:i:s');
        }

         return null;
    }

    public function getTotalSuccessAttribute() {
        return $this->timesheetReportDetails()->where('status', '=', TimesheetReportDetail::StatusSuccess)->count();
    }

    public function getTotalFailedAttribute() {
        return $this->timesheetReportDetails()->where('status', '=', TimesheetReportDetail::StatusFailed)->count();
    }

    public function getTotalPendingAttribute() {
        return $this->timesheetReportDetails()->where('status', '=', TimesheetReportDetail::StatusPending)->count();
    }

    public function unit() {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }

    public function timesheetReportDetails() {
        return $this->hasMany(TimesheetReportDetail::class, 'timesheet_report_id');
    }
}
