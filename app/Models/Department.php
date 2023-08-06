<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    use HasFactory;

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'company_id', 'relation_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'odoo_department_id', 'department_id');
    }
}
