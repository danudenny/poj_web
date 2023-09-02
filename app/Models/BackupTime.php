<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property-read BackupEmployeeTime[] $backupEmployees
 */
class BackupTime extends Model
{
    use HasFactory;

    protected $appends = [
        'start_time_with_timezone',
        'end_time_with_timezone',
        'unit_start_time',
        'unit_end_time',
        'total_backup_string'
    ];

    /**
     * @return string|null
     */
    public function getStartTimeWithTimezoneAttribute(): string|null {
        return Carbon::parse($this->start_time)->setTimezone($this->backup->timezone)->format('Y-m-d H:i:s T');
    }

    /**
     * @return string|null
     */
    public function getEndTimeWithTimezoneAttribute(): string|null {
        return Carbon::parse($this->end_time)->setTimezone($this->backup->timezone)->format('Y-m-d H:i:s T');
    }

    /**
     * @return string|null
     */
    public function getUnitStartTimeAttribute(): string|null {
        return Carbon::parse($this->start_time)->setTimezone($this->backup->timezone)->format('Y-m-d H:i:s');
    }

    /**
     * @return string|null
     */
    public function getUnitEndTimeAttribute(): string|null {
        return Carbon::parse($this->end_time)->setTimezone($this->backup->timezone)->format('Y-m-d H:i:s');
    }

    public function getTotalBackupStringAttribute(): string|null {
        $parsedTime = Carbon::parse($this->end_time)->diff(Carbon::parse($this->start_time));

        $stringify = [];
        if($parsedTime->h > 0) {
            $stringify[] = $parsedTime->h . " Jam";
        }
        if ($parsedTime->i > 0) {
            $stringify[] = $parsedTime->i . " Menit";
        }

        return implode(" ", $stringify);
    }

    public function backup(): BelongsTo {
        return $this->belongsTo(Backup::class, 'backup_id');
    }

    public function backupEmployees(): HasMany {
        return $this->hasMany(BackupEmployeeTime::class, 'backup_time_id');
    }
}
