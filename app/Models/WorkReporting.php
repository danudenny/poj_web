<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property string $title
 * @property string $date
 * @property string $job_type
 * @property string $job_description
 * @property string $image
 * @property integer $employee_id
 * @property string $reference_type
 * @property string $reference_id
 * @property string $created_at
 */
class WorkReporting extends Model
{
    use HasFactory;

    const TypeNormal = "normal";
    const TypeOvertime = "overtime";
    const TypeBackup = "backup";

    protected $fillable = [
        'title',
        'date',
        'job_type',
        'job_description',
        'image',
        'employee_id',
    ];

    protected $appends = [
        'created_at_client_timezone'
    ];

    public function getCreatedAtClientTimezoneAttribute() {
        $time = Carbon::parse($this->created_at, 'UTC');
        $userLocation = request()->header('x-client-timezone');

        if ($userLocation) {
            $time->setTimezone($userLocation);
        }

        return $time->format('Y-m-d H:i:s');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
