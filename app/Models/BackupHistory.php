<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupHistory extends Model
{
    use HasFactory;

    protected $table = 'backup_histories';

    protected $fillable = [
        'backup_id',
        'status'
    ];

    public function backups(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }
}
