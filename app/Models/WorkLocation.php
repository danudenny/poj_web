<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkLocation extends Model
{
    use HasFactory;

    protected $table = 'work_locations';

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'reference_table',
        'reference_id',
        'lat',
        'long',
    ];

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'reference_id', 'id');
    }
}
