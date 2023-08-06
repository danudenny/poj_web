<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KantorPerwakilan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'corporate_id',
        'kanwil_id',
    ];

    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'corporate_id');
    }

    public function kanwil(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'kanwil_id');
    }
}
