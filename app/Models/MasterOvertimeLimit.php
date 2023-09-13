<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property string $name
 * @property int $odoo_overtime_limit_id
 * @property int $daily_work
 * @property int $public_holiday
 * @property int $day_off
 * @property int $sequence
 */
class MasterOvertimeLimit extends Model
{
    protected $table = 'master_overtime_limit';

    use HasFactory;
}
