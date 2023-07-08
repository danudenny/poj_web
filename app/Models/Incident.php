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
 */
class Incident extends Model
{
    use HasFactory;

    const TYPE_EXTERNAL = "external";
    const TYPE_INTERNAL = "internal";

    protected $casts = [
        'created_at'  => 'date:Y-m-d H:i:s',
    ];

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
}
