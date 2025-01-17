<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property string $category
 * @property string $name
 * @property float $latitude
 * @property float $longitude
 * @property string $location_name
 * @property string $incident_time
 * @property string $person
 * @property string $witness
 * @property string $cause
 * @property string $chronology
 * @property string $last_stage
 * @property string $last_status
 * @property string|null $incident_analysis
 * @property string|null $follow_up_incident
 * @property string $created_at
 * @property string $updated_at
 *
 * Relations:
 * @property-read IncidentImage[] $incidentImages
 * @property-read IncidentImage[] $incidentImageFollowUp
 * @property-read IncidentHistory[] $incidentHistories
 * @property-read IncidentApproval[] $incidentApprovals
 * @property Employee $employee
 */
class Incident extends Model
{
    use HasFactory;

    const TYPE_EXTERNAL = "external";
    const TYPE_INTERNAL = "internal";

    protected $casts = [
        'created_at'  => 'date:Y-m-d H:i:s',
    ];

    protected $appends = ['is_finished'];

    public function getIsFinishedAttribute() {
        $totalApproval = ApprovalUser::query()
            ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
            ->join('approval_modules', 'approvals.approval_module_id', '=', 'approval_modules.id')
            ->where('approval_modules.name', '=', ApprovalModule::ApprovalIncident)
            ->where('approvals.unit_relation_id', '=', $this->employee->getLastUnitID())
            ->where('approvals.is_active', '=', true)
            ->orderBy('approval_users.id', 'ASC')
            ->count();

        $incidentHistoryClosureTotal = IncidentHistory::query()
            ->where('incident_id', '=', $this->id)
            ->where('history_type', '=', IncidentHistory::TypeClosure)
            ->count();
        $incidentHistoryApprovalTotal = IncidentHistory::query()
            ->where('incident_id', '=', $this->id)
            ->where('history_type', '=', IncidentHistory::TypeFollowUp)
            ->where('status', '=', IncidentHistory::StatusReject)
            ->count();

        return ($incidentHistoryClosureTotal + $incidentHistoryApprovalTotal) >= $totalApproval;
    }

    /**
     * @return HasMany
     */
    public function incidentImages() {
        return $this->hasMany(IncidentImage::class, 'incident_id')->where('image_type', '=', IncidentImage::TypeIncident);
    }

    /**
     * @return HasMany
     */
    public function incidentImageFollowUp() {
        return $this->hasMany(IncidentImage::class, 'incident_id')->where('image_type', '=', IncidentImage::TypeFollowup);
    }

    /**
     * @return HasMany
     */
    public function incidentHistories() {
        return $this->hasMany(IncidentHistory::class, 'incident_id')->orderBy('created_at', 'ASC');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function incidentApprovals() {
        return $this->hasMany(IncidentApproval::class, 'incident_id');
    }
}
