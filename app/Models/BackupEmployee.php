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
 */
class BackupEmployee extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }
}
