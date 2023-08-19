<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $priority;
 * @property string $status;
 * @property int $backup_id
 * @property int $employee_id
 * @property string|null $notes
 *
 * Relations:
 * @property-read Backup $backup
 * @property-read User $user
 */
class BackupApproval extends Model
{
    const StatusPending = "pending";
    const StatusApproved = "approved";
    const StatusRejected = "rejected";

    use HasFactory;

    protected $appends = ['real_status'];

    public function getRealStatusAttribute() {
        if ($this->priority > 0) {
            $lastStatus = BackupApproval::query()
                ->where('backup_id', '=', $this->backup_id)
                ->where('priority', '<', $this->priority)
                ->where('status', '=', self::StatusPending)
                ->exists();

            if ($lastStatus) {
                return "Waiting Last Approval";
            }
        }

        return $this->status;
    }

    /**
     * @return BelongsTo
     */
    public function backup(): BelongsTo {
        return $this->belongsTo(Backup::class, 'backup_id');
    }

    /**
     * @return BelongsTo
     */
    public function User(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }
}
