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
 * @property int $approval_module_id
 * @property string $name
 * @property int $unit_relation_id
 * @property int $unit_level
 * @property int $department_id
 * @property int $team_id
 * @property int $odoo_job_id
 *
 * Relations:
 * @property-read ApprovalUser[] $approvalUsers
 * @property-read Department $department
 * @property-read Team $team
 * @property-read Job $job
 */
class Approval extends Model
{
    use HasFactory;

    protected $table = 'approvals';

    protected $fillable = [
        'approval_module_id',
        'name',
        'is_active',
        'unit_level',
        'unit_id'
    ];

    protected $appends = [
        'unit',
        'total_approval_user'
    ];

    protected $with = [
        'approvalModule'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'pivot'
    ];

    public function getUnitAttribute() {
        return Unit::query()
            ->where('relation_id', '=', $this->unit_relation_id)
            ->where('unit_level', '=', $this->unit_level)
            ->first();
    }

    public function getTotalApprovalUserAttribute() {
        return $this->approvalUsers()->count();
    }

    public function approvalModule(): BelongsTo
    {
        return $this->belongsTo(ApprovalModule::class);
    }

    public function approvalUsers(): HasMany
    {
        return $this->hasMany(ApprovalUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'approval_users', 'approval_id', 'user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'odoo_job_id', 'odoo_job_id');
    }
}
