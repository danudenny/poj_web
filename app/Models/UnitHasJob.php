<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $unit_relation_id
 * @property int $odoo_job_id
 * @property int $parent_unit_job_id
 *
 * Relations:
 * @property-read Unit $unit
 * @property-read Job $job
 * @property-read UnitHasJob $parent
 */
class UnitHasJob extends Model
{
    use HasFactory;

    protected $appends = [
        'job_name'
    ];

    protected $with = [
        'unit',
        'job'
    ];

    public function getJobNameAttribute() {
        return $this->job->name;
    }

    public function unit() {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }

    public function job() {
        return $this->belongsTo(Job::class, 'odoo_job_id', 'odoo_job_id');
    }

    public function parent() {
        return $this->belongsTo(UnitHasJob::class, 'parent_unit_job_id');
    }
}
