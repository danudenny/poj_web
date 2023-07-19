<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $backup_id
 * @property string $backup_date
 * @property string $start_time
 * @property string $end_time
 *
 * Relations:
 * @property-read Backup $backup
 */
class BackupTime extends Model
{
    use HasFactory;

    public function backup(): BelongsTo {
        return $this->belongsTo(Backup::class, 'backup_id');
    }
}
