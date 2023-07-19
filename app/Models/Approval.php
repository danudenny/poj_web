<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Approval extends Model
{
    use HasFactory;

    protected $table = 'approvals';

    protected $fillable = [
        'approval_module_id',
        'name',
        'is_active',
        'unit_level'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'pivot'
    ];

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
}
