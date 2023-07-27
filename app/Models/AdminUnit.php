<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property int $unit_relation_id
 * @property bool $is_active
 *
 * Relations:
 * @property-read Unit $unit
 * @property-read Employee $employee
 */
class AdminUnit extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo
     */
    public function unit() {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }

    /**
     * @return BelongsTo
     */
    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
