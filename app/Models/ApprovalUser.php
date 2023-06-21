<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalUser extends Model
{
    use HasFactory;

    protected  $table = 'approval_users';

    protected $fillable = [
        'approval_id',
        'user_id',
    ];

    public function approval()
    {
        return $this->hasMany(Approval::class);
    }

    public function user()
    {
        return $this->hasMany(User::class);
    }
}
