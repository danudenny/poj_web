<?php

namespace App\Models;

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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
