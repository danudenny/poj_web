<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalModule extends Model
{
    use HasFactory;

    const ApprovalIncident = "Incident";
    const ApprovalEvent = "Event";
    const ApprovalBackup = "Backup";
    const ApprovalOvertime = "Overtime";
    const ApprovalLeave = "Leave";
    const ApprovalOffsiteAttendance = "Offsite Attendance";

    protected $table = 'approval_modules';

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }
}
