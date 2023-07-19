<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property int $backup_id
 * @property string $status
 * @property string  $notes
 */
class BackupHistory extends Model
{
    use HasFactory;

    protected $table = 'backup_histories';

    protected $casts = [
        'created_at'  => 'date:Y-m-d H:i:s',
    ];

    public function backups(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }
}
