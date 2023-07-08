<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $incident_id
 * @property string $image_type
 * @property string $image_url
 * @property string $created_at
 * @property string $updated_at
 */
class IncidentImage extends Model
{
    use HasFactory;

    const TypeIncident = "incident";
    const TypeFollowup = "follow-up";
}
