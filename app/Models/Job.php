<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attributes:
 * @property-read int $id
 * @property int $odoo_job_id
 */
class Job extends Model
{
    use HasFactory;

    public function employees(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'job_id', 'odoo_job_id');
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_jobs')
            ->withPivot('is_camera', 'is_upload', 'is_reporting', 'is_mandatory_reporting', 'total_reporting');
    }

    public function unitJob(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_jobs')
            ->withPivot('is_camera', 'is_upload', 'is_reporting', 'is_mandatory_reporting', 'total_reporting');
    }
}
