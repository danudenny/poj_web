<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property int $approval_id
 * @property int $employee_id
 * @property int $unit_relation_id
 * @property int $unit_level
 * @property int $department_id
 * @property int $team_id
 * @property int $odoo_job_id
 *
 * Relations:
 * @property-read Approval $approval
 * @property-read Employee $employee
 * @property-read Unit $unit
 * @property-read Department $department
 * @property-read Team $team
 * @property-read Job $job
 */
class ApprovalUser extends Model
{
    use HasFactory;

    protected  $table = 'approval_users';

    public $timestamps = false;
    protected $primaryKey = "approval_id";

    protected $appends = [
        'unit'
    ];

    protected $with = [
        'employee',
        'department',
        'team',
        'job'
    ];

    protected $fillable = [
        'approval_id',
        'user_id',
        'level'
    ];

    public function getUnitAttribute(): Unit|null {
        /**
         * @var Unit $unit
         */
        $unit = Unit::query()
            ->where('relation_id', '=', $this->unit_relation_id)
            ->where('unit_level', '=', $this->unit_level)
            ->first();

        return $unit;
    }

    public function approval()
    {
        return $this->belongsTo(Approval::class, 'approval_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'odoo_job_id', 'odoo_job_id');
    }
}
