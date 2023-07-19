<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $backup_time_id
 * @property int $employee_id
 * @property string|null $check_in_time
 * @property string|null $check_in_lat
 * @property string|null $check_in_long
 * @property string|null $check_out_time
 * @property string|null $check_out_lat
 * @property string|null $check_out_long
 * @property string|null $check_in_timezone
 * @property string|null $check_out_timezone
 *
 * Relations:
 * @property-read BackupTime $backupTime
 */
class BackupEmployeeTime extends Model
{
    use HasFactory;

    public function backupTime(): BelongsTo {
        return $this->belongsTo(BackupTime::class, 'backup_time_id');
    }
}
