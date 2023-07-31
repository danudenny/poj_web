<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequestHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'employee_id',
        'status',
        'created_by',
        'rejected_by',
        'approved_at'
    ];
}
