<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property int $user_id
 *
 * Relations:
 * @property-read User $user
 */
class ApprovalUser extends Model
{
    use HasFactory;

    protected  $table = 'approval_users';

    protected $fillable = [
        'approval_id',
        'user_id',
        'level'
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
